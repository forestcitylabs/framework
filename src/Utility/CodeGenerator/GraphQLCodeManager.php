<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\CodeGenerator;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use GraphQL\GraphQL;
use Nette\PhpGenerator\PhpFile;
use ReflectionAttribute;
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

        // Iterate over classes and discover types within them.
        foreach ($this->type_discovery->discoverClasses() as $class_name) {
            // Get the reflection and build file, namespace and class.
            $reflection = new ReflectionClass($class_name);
            list($file, $namespace, $class) = $this->extractInfo($reflection);

            // Iterate over available types and add them to the map.
            foreach ($reflection->getAttributes(AbstractType::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $type = $attribute->newInstance();
                $type->setName($type->getName() ?? $reflection->getName());
                $this->types[$type->getName()] = new GraphQLFile($reflection->getFileName(), $file, $namespace, $class);
            }
        }

        // Iterate over classes and discover controllers.
        foreach ($this->controller_discovery->discoverClasses() as $class_name) {
            // Get the reflection and build file, namespace and class.
            $reflection = new ReflectionClass($class_name);
            list($file, $namespace, $class) = $this->extractInfo($reflection);

            // Add to controllers.
            $this->controllers[$class->getName()] = new GraphQL($reflection->getFileName(), $file, $namespace, $class);
        }
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getControllers(): array
    {
        return $this->controllers;
    }

    public function getType(string $name): ?GraphQLFile
    {
        return $this->types[$name] ?? null;
    }

    public function getController(string $name): ?GraphQLFile
    {
        return $this->controllers[$name] ?? null;
    }

    public function getTypeByClass(string $class): ?GraphQLFile
    {
        foreach ($this->types as $file) {
            if ($file->getClassLike()->getName() === $class) {
                return $file;
            }
        }
        return null;
    }

    public function addController(string $name, GraphQLFile $controller): static
    {
        $this->controllers[$name] = $controller;
        return $this;
    }

    public function addType(string $name, GraphQLFile $type): static
    {
        $this->types[$name] = $type;
        return $this;
    }

    public function removeController(string $name): static
    {
        unset($this->controllers[$name]);
        return $this;
    }

    public function removeType(string $name): static
    {
        unset($this->types[$name]);
        return $this;
    }

    private function extractInfo(ReflectionClass $reflection): array
    {
        $file = PhpFile::fromCode(file_get_contents($reflection->getFileName()));
        $namespace = $file->getNamespaces()[0];
        $classes = $namespace->getClasses();
        $class = reset($classes);
        return [$file, $namespace, $class];
    }
}
