<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\ClassDiscovery;

interface ClassDiscoveryInterface
{
    /**
     * Returns an array of classes.
     */
    public function discoverClasses(): array;
}
