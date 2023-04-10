<?php

namespace ForestCityLabs\Framework\Utility\ParameterConverter;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\GraphQL\InputResolver;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ReflectionFunctionAbstract;
use ReflectionNamedType;

class InputTypeConverter implements ParameterConverterInterface
{
    public function __construct(
        private MetadataProvider $metadata_provider,
        private InputResolver $input_resolver
    ) {
    }

    public function convertParameters(
        ReflectionFunctionAbstract $reflection,
        array $args = []
    ): array {
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

            // This must be a graphql argument.
            if (count($parameter->getAttributes(GraphQL\Argument::class)) == 0) {
                continue;
            }

            // Get the input type metadata if possible.
            if (null === $input_type = $this->metadata_provider->getInputTypeMetadataByClassName($type->getName())) {
                continue;
            }

            // Resolve the type into the argument.
            $args[$parameter->getName()] = $this->input_resolver->resolve($args[$parameter->getName()], $input_type);
        }

        return $args;
    }
}
