<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\CodeGenerator;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeHelper;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\EnumType as DefinitionEnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GraphQLCodeHelper::class)]
#[Group('utilities')]
#[Group('graphql')]
class GraphQLCodeHelperTest extends TestCase
{
    #[Test]
    public function updateObjectType(): void
    {
        $class = new ClassType('Test');
        $class->addAttribute(GraphQL\ObjectType::class, []);
        $type = new ObjectType(['name' => 'test', 'description' => 'This is the description']);
        $result = GraphQLCodeHelper::updateType($class, $type);
        $this->assertSame($result, $class);
        $this->assertNotEmpty($result->getAttributes());
        foreach ($result->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\ObjectType::class, $attribute->getName());
            $this->assertSame($attribute->getArguments()['name'], 'test');
        }
    }

    #[Test]
    public function updateInterfaceType(): void
    {
        $class = new ClassType('Test');
        $class->addAttribute(GraphQL\InterfaceType::class, []);
        $type = new InterfaceType(['name' => 'test', 'description' => 'This is the description']);
        $result = GraphQLCodeHelper::updateType($class, $type);
        $this->assertSame($result, $class);
        $this->assertNotEmpty($result->getAttributes());
        foreach ($result->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\InterfaceType::class, $attribute->getName());
            $this->assertSame($attribute->getArguments()['name'], 'test');
        }
    }

    #[Test]
    public function updateEnumType(): void
    {
        $class = new EnumType('Test');
        $class->addAttribute(GraphQL\EnumType::class, []);
        $type = new DefinitionEnumType(['name' => 'test', 'description' => 'This is the description']);
        $result = GraphQLCodeHelper::updateType($class, $type);
        $this->assertSame($result, $class);
        $this->assertNotEmpty($result->getAttributes());
        foreach ($result->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\EnumType::class, $attribute->getName());
            $this->assertSame($attribute->getArguments()['name'], 'test');
        }
    }

    #[Test]
    public function updateInputType(): void
    {
        $class = new ClassType('Test');
        $class->addAttribute(GraphQL\InputType::class, []);
        $type = new InputObjectType(['name' => 'test', 'description' => 'This is the description']);
        $result = GraphQLCodeHelper::updateType($class, $type);
        $this->assertSame($result, $class);
        $this->assertNotEmpty($result->getAttributes());
        foreach ($result->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\InputType::class, $attribute->getName());
            $this->assertSame($attribute->getArguments()['name'], 'test');
        }
    }

    #[Test]
    public function buildObjectType(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $type = new ObjectType(['name' => 'test', 'description' => 'This is the description.']);
        $result = GraphQLCodeHelper::buildObjectType($namespace, $class, $type);
        foreach ($namespace->getUses() as $use) {
            $this->assertSame($use, GraphQL::class);
        }
        $this->assertSame($result, $class);
        foreach ($class->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\ObjectType::class, $attribute->getName());
        }
    }

    #[Test]
    public function buildInterfaceType(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $type = new InterfaceType(['name' => 'test', 'description' => 'This is the description.']);
        $result = GraphQLCodeHelper::buildInterfaceType($namespace, $class, $type);
        foreach ($namespace->getUses() as $use) {
            $this->assertSame($use, GraphQL::class);
        }
        $this->assertSame($result, $class);
        foreach ($class->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\InterfaceType::class, $attribute->getName());
        }
    }

    #[Test]
    public function buildInputType(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $type = new InputObjectType(['name' => 'test', 'description' => 'This is the description.']);
        $result = GraphQLCodeHelper::buildInputType($namespace, $class, $type);
        foreach ($namespace->getUses() as $use) {
            $this->assertSame($use, GraphQL::class);
        }
        $this->assertSame($result, $class);
        foreach ($class->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\InputType::class, $attribute->getName());
        }
    }

    #[Test]
    public function buildEnumType(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new EnumType('Test');
        $type = new DefinitionEnumType(['name' => 'test', 'description' => 'This is the description.']);
        $result = GraphQLCodeHelper::buildEnumType($namespace, $class, $type);
        foreach ($namespace->getUses() as $use) {
            $this->assertSame($use, GraphQL::class);
        }
        $this->assertSame($result, $class);
        foreach ($class->getAttributes() as $attribute) {
            $this->assertSame(GraphQL\EnumType::class, $attribute->getName());
        }
    }

    #[Test]
    public function addPropertyField(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $field = new FieldDefinition(['name' => 'tester', 'description' => 'I describe', 'deprecationReason' => 'Yeah', 'type' => Type::listOf(Type::string())]);
        $property = GraphQLCodeHelper::addPropertyField($namespace, $class, $field, 'test', 'string');
        $this->assertContains($property, $class->getProperties());
        $this->assertSame($class->getProperty('test'), $property);
    }

    #[Test]
    public function updatePropertyField(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $field = new FieldDefinition(['name' => 'test', 'type' => Type::string()]);
        $property = $class->addProperty('test');
        $property->addAttribute(GraphQL\Field::class);
        GraphQLCodeHelper::updatePropertyField($namespace, $class, $field, 'string');
        $this->assertSame($property, $class->getProperty('test'));
    }

    #[Test]
    public function addMethodField(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $field = new FieldDefinition(['name' => 'tester', 'description' => 'I describe', 'deprecationReason' => 'Yeah', 'type' => Type::listOf(Type::string())]);
        $method = GraphQLCodeHelper::addMethodField($namespace, $class, $field, 'test', 'string');
        $this->assertContainsEquals($method, $class->getMethods());
        $this->assertSame($method, $class->getMethod('test'));
    }

    #[Test]
    public function updateMethodField(): void
    {
        $namespace = new PhpNamespace('Test');
        $class = new ClassType('Test');
        $field = new FieldDefinition(['name' => 'test', 'type' => Type::string()]);
        $method = $class->addMethod('test');
        $method->addAttribute(GraphQL\Field::class);
        GraphQLCodeHelper::updateMethodField($namespace, $class, $field, 'string');
        $this->assertSame($method, $class->getMethod('test'));
    }

    #[Test]
    public function addParameterArgument(): void
    {
        $namespace = new PhpNamespace('Test');
        $method = new Method('test');
        $arg = new Argument(['name' => 'tester', 'description' => 'This is a description.', 'type' => Type::listOf(Type::string()), 'deprecationReason' => 'Nope.']);
        $param = GraphQLCodeHelper::addParameterArgument($namespace, $method, $arg, 'test', 'string');
        $this->assertContains($param, $method->getParameters());
    }

    #[Test]
    public function updateParameterArgument(): void
    {
        $namespace = new PhpNamespace('Test');
        $method = new Method('test');
        $param = $method->addParameter('test');
        $param->setType('string');
        $param->addAttribute(GraphQL\Argument::class);
        $arg = new Argument(['name' => 'test', 'description' => 'This is a description.', 'type' => Type::listOf(Type::string()), 'deprecationReason' => 'Nope.']);
        $param = GraphQLCodeHelper::updateParameterArgument($namespace, $method, $arg, 'string');
        $this->assertContains($param, $method->getParameters());
    }
}
