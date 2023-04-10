<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility;

use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConverterInterface;
use ForestCityLabs\Framework\Utility\ParameterResolver\ParameterResolverInterface;

class ParameterProcessor
{
    public function __construct(
        private ParameterResolverInterface $resolver,
        private ParameterConverterInterface $converter
    ) {
    }

    public function processParameters($callable, array $params = []): array
    {
        // Create a reflection we can use.
        $reflection = FunctionReflectionFactory::createReflection($callable);

        // Resolve parameters.
        $params = $this->resolver->resolveParameters($reflection, $params);

        // Convert parameters.
        $params = $this->converter->convertParameters($reflection, $params);

        // Return resolved parameters.
        return $params;
    }
}
