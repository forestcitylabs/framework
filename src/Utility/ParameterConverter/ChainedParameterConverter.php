<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility\ParameterConverter;

use ReflectionFunctionAbstract;

class ChainedParameterConverter implements ParameterConverterInterface
{
    public function __construct(
        private array $converters
    ) {
    }

    public function convertParameters(ReflectionFunctionAbstract $reflection, array $args = []): array
    {
        foreach ($this->converters as $converter) {
            assert($converter instanceof ParameterConverterInterface);
            $args = $converter->convertParameters($reflection, $args);
        }

        return $args;
    }
}
