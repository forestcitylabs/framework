<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\CodeGenerator;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use ReflectionClass;

class GraphQLCodeManager
{
    /**
     * @var array<GraphQLFile>
     */
    private array $types;

    /**
     * @var array<GraphQLFile>
     */
    private array $controllers;

    /**
     * @var array<GraphQLFile>
     */
    private array $removed;

    public function __construct(
        private ClassDiscoveryInterface $type_discovery,
        private ClassDiscoveryInterface $controller_discovery,
    ) {
    }

    public function initialize(): void
    {
        // Reset the type and controller arrays.
        $this->types = [];
        $this->controllers = [];
        $this->removed = [];

        // Iterate over classes and discover types within them.
        foreach ($this->type_discovery->discoverClasses() as $class_name) {
            // Get the reflection and build file, namespace and class.
            $reflection = new ReflectionClass($class_name);
            list($file, $namespace, $class) = $this->extractInfo($reflection);

            // Add to types.
            $this->types[$namespace->getName() . '\\' . $class->getName()]
                = new GraphQLFile($reflection->getFileName(), $file, $namespace, $class);
        }

        // Iterate over classes and discover controllers.
        foreach ($this->controller_discovery->discoverClasses() as $class_name) {
            // Get the reflection and build file, namespace and class.
            $reflection = new ReflectionClass($class_name);
            list($file, $namespace, $class) = $this->extractInfo($reflection);

            // Add to controllers.
            $this->controllers[$namespace->getName() . '\\' . $class->getName()]
                = new GraphQLFile($reflection->getFileName(), $file, $namespace, $class);
        }
    }

    public function getUnmappedTypes(string $attribute_type, string $instance_of = ClassType::class): array
    {
        return array_filter($this->types, function (GraphQLFile $info) use ($instance_of, $attribute_type): bool {
            // Must be an instance of this type.
            if (!$info->getClassLike() instanceof $instance_of) {
                return false;
            }

            // Check the attributes.
            foreach ($info->getClassLike()->getAttributes() as $attribute) {
                if ($attribute->getName() === $attribute_type) {
                    return false;
                }
            }
            return true;
        });
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getType(string $type_name): ?GraphQLFile
    {
        foreach ($this->types as $type) {
            foreach ($type->getClassLike()->getAttributes() as $attribute) {
                if (
                    in_array($attribute->getName(), [
                    GraphQL\ObjectType::class,
                    GraphQL\InterfaceType::class,
                    GraphQL\EnumType::class,
                    GraphQL\InputType::class,
                    ])
                ) {
                    $args = $attribute->getArguments();
                    $name = $args['name'] ?? $type->getClassLike()->getName();
                    if ($name === $type_name) {
                        return $type;
                    }
                }
            }
        }
        return null;
    }

    public function getController(string $name): ?GraphQLFile
    {
        return $this->controllers[$name] ?? null;
    }

    public function getControllerForField(string $type_name, string $field_name): ?array
    {
        foreach ($this->controllers as $controller) {
            foreach ($controller->getClass()->getMethods() as $method) {
                $type_match = false;
                $field_match = false;
                foreach ($method->getAttributes() as $attribute) {
                    // This is the correct type.
                    if ($attribute->getName() === $type_name) {
                        $type_match = true;
                    }
                    if ($attribute->getName() === GraphQL\Field::class) {
                        $match = $attribute->getArguments()['name'] ?? $method->getName();
                        if ($match === $field_name) {
                            $field_match = true;
                        }
                    }
                    if ($type_match && $field_match) {
                        return [$controller, $method];
                    }
                }
            }
        }

        return null;
    }

    public function getTypeByClass(string $class): ?GraphQLFile
    {
        return $this->types[$class] ?? null;
    }

    public function addController(GraphQLFile $controller): static
    {
        $this->controllers[$controller->getNamespace()->getName() . '\\' . $controller->getClassLike()->getName()] = $controller;
        return $this;
    }

    public function addType(GraphQLFile $type): static
    {
        $this->types[$type->getNamespace()->getName() . '\\' . $type->getClassLike()->getName()] = $type;
        return $this;
    }

    public function removeController(string $name): static
    {
        $this->removed[$name] = $this->controllers[$name];
        unset($this->controllers[$name]);
        return $this;
    }

    public function removeType(string $name): static
    {
        $this->removed[$name] = $this->types[$name];
        unset($this->types[$name]);
        return $this;
    }

    /**
     * @return array<GraphQLFile>
     */
    public function getRemoved(): array
    {
        return $this->removed;
    }

    private function extractInfo(ReflectionClass $reflection): array
    {
        $file = PhpFile::fromCode(file_get_contents($reflection->getFileName()));
        $namespace = $file->getNamespaces()[$reflection->getNamespaceName()];
        $class = $file->getClasses()[$reflection->getName()];
        return [$file, $namespace, $class];
    }
}
