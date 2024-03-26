<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\ClassDiscovery;

use DirectoryIterator;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use ReflectionClass;

class ScanDirectoryDiscovery implements ClassDiscoveryInterface
{
    public function __construct(private array $paths = [])
    {
    }

    public function discoverClasses(): array
    {
        $classes = [];
        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            foreach (new DirectoryIterator($path) as $file) {
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
                foreach ($declared as $class) {
                    $reflection = new ReflectionClass($class);
                    if ($reflection->getFileName() !== false && stristr($reflection->getFileName(), $path)) {
                        $classes[] = $class;
                    }
                }
            }
        }

        // Return found classes.
        return $classes;
    }
}
