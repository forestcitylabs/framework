<?php

namespace ForestCityLabs\Framework\Utility;

use Closure;
use Exception;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class FunctionReflectionFactory
{
    public static function createReflection($callable): ReflectionFunctionAbstract
    {
        // Closure
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        // Array callable
        if (is_array($callable)) {
            [$class, $method] = $callable;

            if (!method_exists($class, $method)) {
                throw new Exception('Invalid callable.');
            }

            return new ReflectionMethod($class, $method);
        }

        // Callable object (i.e. implementing __invoke())
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return new ReflectionMethod($callable, '__invoke');
        }

        // Standard function
        if (is_string($callable) && function_exists($callable)) {
            return new ReflectionFunction($callable);
        }

        throw new Exception('Invalid callable.');
    }
}
