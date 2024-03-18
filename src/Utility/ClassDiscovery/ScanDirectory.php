<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\ClassDiscovery;

use DirectoryIterator;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use ReflectionClass;

class ScanDirectory implements ClassDiscoveryInterface
{
    public function __construct(private string $directory)
    {
    }

    public function discoverClasses(): array
    {
        foreach (new DirectoryIterator($this->directory) as $file) {
            // Skip dot files.
            if ($file->isDot()) {
                continue;
            }

            // Skip non-php files.
            if ($file->getExtension() !== "php") {
                continue;
            }

            // Include the files.
            require_once $file->getRealPath();

            // Get declared classes.
            $declared = get_declared_classes();

            // Iterate and filter classes.
            $classes = [];
            foreach ($declared as $class) {
                $reflection = new ReflectionClass($class);
                if ($reflection->getFileName() !== false && stristr($reflection->getFileName(), $this->directory)) {
                    $classes[] = $class;
                }
            }

            // Return found classes.
            return $classes;
        }
    }
}
