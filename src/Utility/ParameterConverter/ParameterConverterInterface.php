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

interface ParameterConverterInterface
{
    /**
     * Accepts a reflection function and returns an array of parameters keyed by name.
     *
     * @param ReflectionFunctionAbstract $reflection The function to resolve parameters for.
     * @param array                      $args       Any existing arguments keyed by name.
     *
     * @return array An array of parameters keyed by name.
     */
    public function convertParameters(ReflectionFunctionAbstract $reflection, array $args = []): array;
}
