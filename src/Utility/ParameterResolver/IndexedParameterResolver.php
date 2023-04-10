<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility\ParameterResolver;

use ReflectionFunctionAbstract;
use ReflectionNamedType;

class IndexedParameterResolver implements ParameterResolverInterface
{
    public function resolveParameters(ReflectionFunctionAbstract $reflection, array $args = []): array
    {
        // Remove numerically indexed items from arguments.
        $candidates = [];
        foreach ($args as $k => $v) {
            if (is_int($k)) {
                $candidates[] = $v;
                unset($args[$k]);
            }
        }

        // If there are no candidates to re-wire we can exit early.
        if (0 === count($candidates)) {
            return $args;
        }

        foreach ($reflection->getParameters() as $parameter) {
            // Do not try to resolve arguments with a value.
            if (isset($args[$parameter->getName()])) {
                continue;
            }

            // Get the parameter type.
            if (null === $type = $parameter->getType()) {
                continue;
            }

            // Can only operate on named types.
            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            // Cannot operate on built-in types.
            if ($type->isBuiltin()) {
                continue;
            }

            // Attempt to map a candidate.
            foreach ($candidates as $candidate) {
                $type_name = $type->getName();
                if ($candidate instanceof $type_name) {
                    $args[$parameter->getName()] = $candidate;
                }
            }
        }

        return $args;
    }
}
