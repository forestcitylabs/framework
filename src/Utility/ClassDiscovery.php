<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility;

use DirectoryIterator;
use PhpToken;

class ClassDiscovery
{
    public static function discoverClasses(string $dir): iterable
    {
        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) {
                continue;
            }
            if ($file->getExtension() !== "php") {
                continue;
            }
            $namespace = "";
            foreach (PhpToken::tokenize(file_get_contents($file->getRealPath())) as $token) {
                // Detect the namespace for this class file.
                if ($token->is(T_NAMESPACE)) {
                    $namespace = $token->text;
                }

                // Return the fully qualified class name.
                if ($token->is(T_CLASS)) {
                    yield $namespace . "\\" . $token->text;
                }
            }
        }
    }
}
