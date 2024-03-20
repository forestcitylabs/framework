<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\ClassDiscovery;

class ManualDiscovery implements ClassDiscoveryInterface
{
    public function __construct(private array $classes)
    {
    }

    public function discoverClasses(): array
    {
        return $this->classes;
    }
}
