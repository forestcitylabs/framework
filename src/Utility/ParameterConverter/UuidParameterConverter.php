<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility\ParameterConverter;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class UuidParameterConverter implements ParameterConverterInterface
{
    public function convertParameters(ReflectionFunctionAbstract $reflection, array $args = []): array
    {
        foreach ($reflection->getParameters() as $parameter) {
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

            // Valid interfaces to convert.
            $implements = class_implements($type->getName()) + [$type->getName() => $type->getName()];

            // We should convert this value to a Uuid.
            if (
                isset($implements[UuidInterface::class])
                && isset($args[$parameter->getName()])
                && is_string($args[$parameter->getName()])
            ) {
                try {
                    $args[$parameter->getName()] = Uuid::fromString($args[$parameter->getName()]);
                } catch (InvalidUuidStringException $e) {
                    throw new ParameterConversionException(previous: $e);
                }
            }
        }

        return $args;
    }
}
