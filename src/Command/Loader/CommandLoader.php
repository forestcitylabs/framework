<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command\Loader;

use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class CommandLoader implements CommandLoaderInterface
{
    private array $commands = [];

    public function __construct(
        private ClassDiscoveryInterface $discovery,
        private CacheItemPoolInterface $cache_pool,
        private ContainerInterface $container,
    ) {
        $cache = $cache_pool->getItem('core.commands');
        if (!$cache->isHit()) {
            // Iterate over commands.
            foreach ($discovery->discoverClasses() as $command) {
                $reflection = new ReflectionClass($command);

                // Check for the AsCommand attribute.
                if (count($reflection->getAttributes(AsCommand::class)) > 0) {
                    foreach ($reflection->getAttributes(AsCommand::class) as $metadata) {
                        $metadata = $metadata->newInstance();
                        $this->commands[$metadata->name] = $command;
                    }
                } else {
                    $command = $this->container->get($command);
                    $this->commands[$command->getName()] = $command::class;
                }
            }
            $cache_pool->save($cache->set($this->commands));
        } else {
            $this->commands = $cache->get();
        }
    }

    public function getNames(): array
    {
        return array_keys($this->commands);
    }

    public function get(string $name): Command
    {
        return $this->container->get($this->commands[$name]);
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }
}
