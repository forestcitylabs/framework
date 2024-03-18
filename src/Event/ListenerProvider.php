<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Event;

use ForestCityLabs\Framework\Event\Attribute\EventListener;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use ReflectionClass;

class ListenerProvider implements ListenerProviderInterface
{
    public const PRIORITY_FIRST = -100;
    public const PRIORITY_EARLY = -50;
    public const PRIORITY_NORMAL = 0;
    public const PRIORITY_LATE = 50;
    public const PRIORITY_LAST = 100;

    private array $data = [];

    public function __construct(
        private ClassDiscoveryInterface $discovery,
        private CacheItemPoolInterface $cache,
        private ContainerInterface $container
    ) {
        $item = $cache->getItem('core.event.listeners');
        if (!$item->isHit()) {
            // Create registry of event listeners.
            foreach ($discovery->discoverClasses() as $listener_class) {
                $reflection = new ReflectionClass($listener_class);
                foreach ($reflection->getAttributes(EventListener::class) as $attribute) {
                    $listener = $attribute->newInstance();
                    $this->data[$listener->getEventClass()][$listener->getPriority()][]
                        = $reflection->getName();
                }
            }

            // Priority sort all listeners.
            foreach ($this->data as $event_name => $listeners) {
                ksort($this->data[$event_name]);
            }

            $item->set($this->data);
            $cache->save($item);
        } else {
            $this->data = $item->get();
        }
    }

    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->data[$event::class] ?? [] as $listeners) {
            foreach ($listeners as $listener) {
                yield $this->container->get($listener);
            }
        }
    }
}
