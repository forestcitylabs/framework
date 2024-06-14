<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\Field as ObjectFieldAttribute;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument as InputArgumentAttribute;
use ForestCityLabs\Framework\GraphQL\Attribute\EnumType as EnumTypeAttribute;
use ForestCityLabs\Framework\GraphQL\Attribute\InputType as InputTypeAttribute;
use ForestCityLabs\Framework\GraphQL\Attribute\InterfaceType as InterfaceTypeAttribute;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType as ObjectTypeAttribute;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TypeRegistry
{
    private array $types;

    public function __construct(
        private MetadataProvider $metadata_provider,
        private PropertyFieldResolver $property_field_resolver,
        private MethodFieldResolver $method_field_resolver
    ) {
    }

    public function getType(string $name): ?Type
    {
        // Return a scalar type if possible.
        switch ($name) {
            case 'String':
                return Type::string();
                break;
            case 'Int':
                return Type::int();
                break;
            case 'Float':
                return Type::float();
                break;
            case 'Boolean':
                return Type::boolean();
                break;
            case 'ID':
                return Type::id();
                break;
        }

        // Build the type.
        if (!isset($this->types[$name])) {
            // Get the metadata.
            if (null === $metadata = $this->metadata_provider->getTypeMetadata($name)) {
                return null;
            }

            // Determine if this is an input or type.
            switch ($metadata::class) {
                case InterfaceTypeAttribute::class:
                    $this->types[$name] = $this->buildInterfaceType($metadata);
                    break;
                case InputTypeAttribute::class:
                    $this->types[$name] = $this->buildInputType($metadata);
                    break;
                case EnumTypeAttribute::class:
                    $this->types[$name] = $this->buildEnumType($metadata);
                    break;
                case ObjectTypeAttribute::class:
                default:
                    $this->types[$name] = $this->buildObjectType($metadata);
                    break;
            }
        }

        // Return the configuration.
        return $this->types[$name];
    }

    private function buildInputType(InputTypeAttribute $input_metadata): Type
    {
        return new InputObjectType([
            'name' => $input_metadata->getName(),
            'description' => $input_metadata->getDescription(),
            'fields' => function () use ($input_metadata): iterable {
                foreach ($input_metadata->getArguments() as $argument_metadata) {
                    yield $this->buildInputArgument($argument_metadata);
                }
            },
        ]);
    }

    private function buildObjectType(ObjectTypeAttribute $type_metadata): Type
    {
        return new ObjectType([
            'name' => $type_metadata->getName(),
            'description' => $type_metadata->getDescription(),
            'fields' => function () use ($type_metadata): iterable {
                foreach ($type_metadata->getFields() as $field_metadata) {
                    yield $this->buildObjectField($field_metadata);
                }
            },
            'interfaces' => function () use ($type_metadata): iterable {
                foreach ($type_metadata->getInterfaces() as $interface) {
                    yield $this->getType($interface);
                }
            }
        ]);
    }

    private function buildInterfaceType(InterfaceTypeAttribute $metadata): Type
    {
        return new InterfaceType([
            'name' => $metadata->getName(),
            'description' => $metadata->getDescription(),
            'fields' => function () use ($metadata): iterable {
                foreach ($metadata->getFields() as $field) {
                    yield $this->buildObjectField($field);
                }
            }
        ]);
    }

    public function buildEnumType(EnumTypeAttribute $metadata): Type
    {
        $values = [];
        foreach ($metadata->getValues() as $value) {
            $values[$value->getName()] = [
                'name' => $value->getName(),
                'value' => $value->getCase(),
                'description' => $value->getDescription(),
                'deprecationReason' => $value->getDeprecationReason(),
            ];
        }
        return new EnumType([
            'name' => $metadata->getName(),
            'description' => $metadata->getDescription(),
            'values' => $values,
        ]);
    }

    private function buildObjectField(ObjectFieldAttribute $field_metadata): array
    {
        // Parse this fields type.
        $type = $this->getType($field_metadata->getType());

        // If this is a list wrap.
        if ($field_metadata->getList()) {
            $type = Type::listOf(Type::nonNull($type));
        }

        // If this is not null wrap.
        if ($field_metadata->getNotNull()) {
            $type = Type::nonNull($type);
        }

        return [
            'name' => $field_metadata->getName(),
            'description' => $field_metadata->getDescription(),
            'type' => $type,
            'args' => $this->parseArguments($field_metadata),
            'resolve' => function ($value, array $args, $context) use ($field_metadata) {
                // Use correct field resolver.
                switch ($field_metadata->getAttributeType()) {
                    case ObjectFieldAttribute::TYPE_METHOD:
                        return $this->method_field_resolver->resolveField(
                            $field_metadata,
                            $value,
                            $args,
                            $context
                        );
                        break;
                    case ObjectFieldAttribute::TYPE_PROPERTY:
                        return $this->property_field_resolver->resolveField(
                            $field_metadata,
                            $value,
                            $args,
                            $context
                        );
                        break;
                }
            },
        ];
    }

    private function buildInputArgument(InputArgumentAttribute $argument_metadata): array
    {
        // Parse this arguments type.
        $type = $this->getType($argument_metadata->getType());

        // If this is a list wrap.
        if ($argument_metadata->getList()) {
            $type = Type::listOf(Type::nonNull($type));
        }

        // If this is not null wrap.
        if ($argument_metadata->getNotNull()) {
            $type = Type::nonNull($type);
        }

        return [
            'name' => $argument_metadata->getName(),
            'description' => $argument_metadata->getDescription(),
            'type' => $type,
        ];
    }

    private function parseArguments(ObjectFieldAttribute $field_metadata): array
    {
        $args = [];
        foreach ($field_metadata->getArguments() as $argument_metadata) {
            $type = $this->getType($argument_metadata->getType());

            // If this is a list wrap.
            if ($argument_metadata->getList()) {
                $type = Type::listOf(Type::nonNull($type));
            }

            // If this is not null wrap.
            if ($argument_metadata->getNotNull()) {
                $type = Type::nonNull($type);
            }

            $args[] = [
                'name' => $argument_metadata->getName(),
                'description' => $argument_metadata->getDescription(),
                'type' => $type,
            ];
        }
        return $args;
    }
}
