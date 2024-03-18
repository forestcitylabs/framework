<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\ClassDiscovery;

class ChainedDiscovery implements ClassDiscoveryInterface
{
    public function __construct(private array $discovery)
    {
    }

    public function discoverClasses(): array
    {
        $classes = [];
        foreach ($this->discovery as $discovery) {
            $classes = array_merge($classes, $discovery->discoverClasses());
        }
        return array_unique($classes);
    }
}
