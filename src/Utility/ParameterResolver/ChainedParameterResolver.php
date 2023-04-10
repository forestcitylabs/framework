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

class ChainedParameterResolver implements ParameterResolverInterface
{
    public function __construct(
        private array $resolvers
    ) {
    }

    public function resolveParameters(ReflectionFunctionAbstract $reflection, array $args = []): array
    {
        foreach ($this->resolvers as $resolver) {
            assert($resolver instanceof ParameterResolverInterface);
            $args = $resolver->resolveParameters($reflection, $args);
        }

        return $args;
    }
}
