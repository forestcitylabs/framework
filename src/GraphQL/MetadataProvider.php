<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\EnumType;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Attribute\InputType;
use ForestCityLabs\Framework\GraphQL\Attribute\InterfaceType;
use ForestCityLabs\Framework\GraphQL\Attribute\Mutation;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType;
use ForestCityLabs\Framework\GraphQL\Attribute\Query;
use LogicException;
use Psr\Cache\CacheItemPoolInterface;
use Ramsey\Uuid\UuidInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use Traversable;

class MetadataProvider
{
    private array $metadata = [];

    public function __construct(
        private array $types,
        private array $controllers,
        private CacheItemPoolInterface $cache
    ) {
        $item = $cache->getItem('core.graphql.metadata');
        if (!$item->isHit()) {
            // Parse types.
            $this->parseTypes($types);

            // Parse fields.
            $this->parseFields();

            // Add interfaces.
            $this->mapInterfaces();

            // Parse controllers.
            $this->parseControllers($controllers);

            // Cache the metadata.
            $cache->save($item->set($this->metadata));
        } else {
            $this->metadata = $item->get();
        }
    }

    public function getAllTypeMetadata(): iterable
    {
        return $this->metadata;
    }

    public function getTypeMetadata(string $name): ?AbstractType
    {
        return $this->metadata[$name] ?? null;
    }

    public function getMetadataByClassName(string $class_name): iterable
    {
        foreach ($this->metadata as $type) {
            if ($type->getClassName() === $class_name) {
                yield $type;
            }
        }
    }

    public function getObjectTypeMetadataByClassName(string $class_name): ?ObjectType
    {
        foreach ($this->getMetadataByClassName($class_name) as $metadata) {
            if ($metadata instanceof ObjectType) {
                return $metadata;
            }
        }
        return null;
    }

    public function getInputTypeMetadataByClassName(string $class_name): ?InputType
    {
        foreach ($this->getMetadataByClassName($class_name) as $metadata) {
            if ($metadata instanceof InputType) {
                return $metadata;
            }
        }
        return null;
    }

    public function getInterfaceTypeMetadataByClassName(string $class_name): ?InterfaceType
    {
        foreach ($this->getMetadataByClassName($class_name) as $metadata) {
            if ($metadata instanceof InterfaceType) {
                return $metadata;
            }
        }
        return null;
    }

    private function parseTypes(array $types): void
    {
        foreach ($types as $type) {
            // Get a reflection for the type.
            $reflection = new ReflectionClass($type);

            // Iterate over type attributes.
            foreach (
                $reflection->getAttributes(
                    AbstractType::class,
                    ReflectionAttribute::IS_INSTANCEOF
                ) as $attribute
            ) {
                // Create type attribute.
                $type = $attribute->newInstance();
                $type->setClassName($reflection->getName());

                // Reasonable defaults.
                $type->setName($type->getName() ?? $reflection->getShortName());

                // Add type metadata.
                $this->metadata[$type->getName()] = $type;
            }
        }
    }

    private function parseFields(): void
    {
        // Iterate over all defined types.
        foreach ($this->metadata as $type) {
            // If this is a mapped type.
            if (null !== $type->getClassName()) {
                // Parse the fields for this type.
                $reflection = new ReflectionClass($type->getClassName());

                // Determine which fields to parse.
                switch ($type::class) {
                    case InterfaceType::class:
                    case ObjectType::class:
                        foreach ($this->parsePropertyFields($reflection) as $field) {
                            $type->addField($field);
                        }
                        foreach ($this->parseMethodFields($reflection) as $field) {
                            $type->addField($field);
                        }
                        break;
                    case InputType::class:
                        foreach ($this->parsePropertyArguments($reflection) as $argument) {
                            $type->addArgument($argument);
                        }
                        break;
                    case EnumType::class:
                }
            }
        }
    }

    private function mapInterfaces(): void
    {
        foreach ($this->metadata as $metadata) {
            if ($metadata instanceof ObjectType) {
                if ($metadata->getClassName() !== null) {
                    foreach (class_implements($metadata->getClassName()) as $interface) {
                        if (null !== $interface = $this->getInterfaceTypeMetadataByClassName($interface)) {
                            $metadata->addInterface($interface->getName());
                        }
                    }
                }
            }
        }
    }

    private function parseControllers(array $controllers): void
    {
        foreach ($controllers as $controller) {
            // Get a reflection class for the controller.
            $reflection = new ReflectionClass($controller);

            // Parse the fields from this controller.
            foreach ($this->parseMethodFields($reflection) as $type => $field) {
                if (!array_key_exists($type, $this->metadata)) {
                    $this->metadata[$type] = new ObjectType($type);
                }
                $this->metadata[$type]->addField($field);
            }
        }
    }

    private function parsePropertyFields(ReflectionClass $reflection): iterable
    {
        foreach ($reflection->getProperties() as $property) {
            // Iterate over field attributes.
            foreach ($property->getAttributes(Field::class) as $attribute) {
                // Set type and data.
                $field = $attribute->newInstance();
                $field->setAttributeType(Field::TYPE_PROPERTY);
                $field->setAttributeName($property->getName());

                // Reasonable defaults.
                $field->setName($field->getName() ?? $property->getName());
                $field->setType($field->getType() ?? $this->mapOutputType($property->getType()));
                $field->setNotNull($field->getNotNull() ?? $this->mapNotNull($property->getType()));
                $field->setList($field->getList() ?? $this->mapList($property->getType()));

                // Yield the field attribute.
                yield $field;
            }
        }
    }

    private function parsePropertyArguments(ReflectionClass $reflection): iterable
    {
        foreach ($reflection->getProperties() as $property) {
            // Iterate over field attributes.
            foreach ($property->getAttributes(Argument::class) as $attribute) {
                // Set type and data.
                $argument = $attribute->newInstance();
                $argument->setAttributeType(Argument::TYPE_PROPERTY);
                $argument->setAttributeName($property->getName());

                // Reasonable defaults.
                $argument->setName($argument->getName() ?? $property->getName());
                $argument->setType($argument->getType() ?? $this->mapInputType($property->getType()));
                $argument->setNotNull($argument->getNotNull() ?? $this->mapNotNull($property->getType()));
                $argument->setList($argument->getList() ?? $this->mapList($property->getType()));

                // Yield the argument attribute.
                yield $argument;
            }
        }
    }

    private function parseMethodFields(ReflectionClass $reflection): iterable
    {
        foreach ($reflection->getMethods() as $method) {
            // Iterate over field attributes.
            foreach ($method->getAttributes(Field::class) as $attribute) {
                // Create the field attribute.
                $field = $attribute->newInstance();
                $field->setAttributeType(Field::TYPE_METHOD);
                $field->setAttributeName($reflection->getName() . '::' . $method->getName());

                // Reasonable defaults.
                $field->setName($field->getName() ?? $method->getName());
                $field->setType($field->getType() ?? $this->mapOutputType($method->getReturnType()));
                $field->setNotNull($field->getNotNull() ?? $this->mapNotNull($method->getReturnType()));
                $field->setList($field->getList() ?? $this->mapList($method->getReturnType()));

                // Parse arguments for this method.
                foreach ($this->parseParameterArguments($method) as $argument) {
                    $field->addArgument($argument);
                }

                // This is a query field.
                if (count($method->getAttributes(Query::class)) > 0) {
                    yield 'Query' => $field;
                }

                // This is a mutation field.
                if (count($method->getAttributes(Mutation::class)) > 0) {
                    yield 'Mutation' => $field;
                }

                // This is a field on an object type.
                foreach ($this->getMetadataByClassName($reflection->getName()) as $metadata) {
                    yield $metadata->getName() => $field;
                }
            }
        }
    }

    private function parseParameterArguments(ReflectionMethod $method): iterable
    {
        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Argument::class) as $attribute) {
                $argument = $attribute->newInstance();
                $argument->setAttributeType(Argument::TYPE_PARAMETER);
                $argument->setAttributeName($parameter->getName());

                // Reasonable defaults.
                $argument->setName($argument->getName() ?? $parameter->getName());
                $argument->setType($argument->getType() ?? $this->mapOutputType($parameter->getType()));
                $argument->setNotNull($argument->getNotNull() ?? $this->mapNotNull($parameter->getType()));
                $argument->setList($argument->getList() ?? false);

                // Yield the argument attribute.
                yield $argument;
            }
        }
    }

    private function mapOutputType(?ReflectionType $type): string
    {
        if (null === $type) {
            throw new LogicException('Cannot auto-detect type.');
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException('Can only auto-detect types for single typed properties.');
        }

        // Detect id type.
        if (
            UuidInterface::class === $type->getName()
            || is_subclass_of($type->getName(), UuidInterface::class)
        ) {
            return 'ID';
        }

        // Detect scalar types.
        switch ($type->getName()) {
            case 'bool':
                return 'Boolean';
                break;
            case 'int':
                return 'Int';
                break;
            case 'float':
                return 'Float';
                break;
            case 'string':
                return 'String';
                break;
        }

        // Attempt to map type by class name as a last resort.
        foreach ($this->getMetadataByClassName($type->getName()) as $metadata) {
            if ($metadata instanceof ObjectType) {
                return $metadata->getName();
            }
        }

        throw new LogicException(sprintf('Unable to map type "%s".', $type->getName()));
    }

    private function mapInputType(?ReflectionType $type): string
    {
        if (null === $type) {
            throw new LogicException('Cannot auto-detect type.');
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException('Can only auto-detect types for single typed properties.');
        }

        // Detect id type.
        if (
            UuidInterface::class === $type->getName()
            || is_subclass_of($type->getName(), UuidInterface::class)
        ) {
            return 'ID';
        }

        // Detect scalar types.
        switch ($type->getName()) {
            case 'bool':
                return 'Boolean';
                break;
            case 'int':
                return 'Int';
                break;
            case 'float':
                return 'Float';
                break;
            case 'string':
                return 'String';
                break;
        }

        // Attempt to map type by class name as a last resort.
        foreach ($this->getMetadataByClassName($type->getName()) as $metadata) {
            if ($metadata instanceof InputType) {
                return $metadata->getName();
            }
        }

        throw new LogicException(sprintf('Unable to map type "%s".', $type->getName()));
    }

    private function mapNotNull(?ReflectionType $type): bool
    {
        if (null === $type) {
            throw new LogicException('Cannot auto-detect nullable.');
        }

        return !$type->allowsNull();
    }

    private function mapList(?ReflectionType $type): bool
    {
        if (null === $type) {
            throw new LogicException('Cannot auto-detect list.');
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException('Can only auto-detect list for single typed properties.');
        }

        return 'array' === $type->getName() || is_a($type->getName(), Traversable::class, true);
    }
}
