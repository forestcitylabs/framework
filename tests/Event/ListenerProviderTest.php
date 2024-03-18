<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Event;

use ForestCityLabs\Framework\Event\Attribute\EventListener;
use ForestCityLabs\Framework\Event\ListenerProvider;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

#[CoversClass(ListenerProvider::class)]
#[Group("event")]
#[UsesClass(EventListener::class)]
#[UsesClass(ManualDiscovery::class)]
class ListenerProviderTest extends TestCase
{
    #[Test]
    public function buildListenerCache(): void
    {
        // Create mock objects.
        $item = $this->createConfiguredMock(CacheItemInterface::class, ['isHit' => false]);
        $pool = $this->createConfiguredMock(CacheItemPoolInterface::class, ['getItem' => $item]);
        $container = $this->createConfiguredMock(ContainerInterface::class, ['get' => new TestEventListener()]);

        // Set some assertions.
        $item->expects($this->once())->method('set');
        $pool->expects($this->once())->method('save');

        // Create the listener provider.
        new ListenerProvider(new ManualDiscovery([TestEventListener::class]), $pool, $container);
    }

    #[Test]
    public function cachedListeners(): void
    {
        // Create mock objects.
        $item = $this->createConfiguredMock(CacheItemInterface::class, ['isHit' => true]);
        $pool = $this->createConfiguredMock(CacheItemPoolInterface::class, ['getItem' => $item]);
        $container = $this->createConfiguredMock(ContainerInterface::class, ['get' => new TestEventListener()]);

        // Set some assertions.
        $item->expects($this->once())->method('get')->willReturn([]);
        $pool->expects($this->never())->method('save');

        // Create the listener provider.
        new ListenerProvider(new ManualDiscovery([TestEventListener::class]), $pool, $container);
    }

    #[Test]
    public function getListener(): void
    {
        // Create mock objects.
        $item = $this->createConfiguredMock(CacheItemInterface::class, ['isHit' => false]);
        $pool = $this->createConfiguredMock(CacheItemPoolInterface::class, ['getItem' => $item]);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(TestEventListener::class)->willReturn(new TestEventListener());

        // Create the listener provider.
        $provider = new ListenerProvider(new ManualDiscovery([TestEventListener::class]), $pool, $container);
        $listeners = $provider->getListenersForEvent(new TestEvent());

        // Assert that listeners are correct.
        foreach ($listeners as $listener) {
            $this->assertInstanceOf(TestEventListener::class, $listener);
        }
    }
}
