<?php

namespace ForestCityLabs\Framework\Utility\ParameterConverter;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class DateTimeParameterConverter implements ParameterConverterInterface
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

            // We should convert this value to a date.
            if (
                isset($implements[DateTimeInterface::class])
                && isset($args[$parameter->getName()])
                && is_string($args[$parameter->getName()])
            ) {
                try {
                    $args[$parameter->getName()] = new DateTimeImmutable($args[$parameter->getName()]);
                } catch (Exception $e) {
                    throw new ParameterConversionException(previous: $e);
                }
            }
        }

        return $args;
    }
}
