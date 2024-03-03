<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility\ParameterResolver;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class ContainerParameterResolver implements ParameterResolverInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function resolveParameters(ReflectionFunctionAbstract $reflection, array $args = []): array
    {
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

            // Try to resolve using the container.
            try {
                $args[$parameter->getName()] = $this->container->get($type->getName());
            } catch (NotFoundExceptionInterface) {
                // No action.
            }
        }

        // Return the resolved parameter array.
        return $args;
    }
}
