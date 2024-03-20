<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility;

use Doctrine\Common\Collections\Collection;
use ForestCityLabs\Framework\Utility\CodeGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(CodeGenerator::class)]
#[Group("utilities")]
class CodeGeneratorTest extends TestCase
{
    #[Test]
    public function addIdProperty(): void
    {
        // Create values for testing.
        $namespace = new PhpNamespace('TestNamespace');
        $class = new ClassType('TestClass', $namespace);

        // Run the add id method.
        CodeGenerator::addIdProperty($class, $namespace);

        // Make assertions.
        $this->assertTrue($class->hasMethod('getId'));
        $this->assertTrue($class->hasProperty('id'));
        $this->assertSame(Uuid::class, $class->getProperty('id')->getType());
        $this->assertSame(Uuid::class, $class->getMethod('getId')->getReturnType());
    }

    #[Test]
    public function addGetter(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');

        // Run the add getter.
        CodeGenerator::addGetter($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('getBeans'));
    }

    #[Test]
    public function addSetter(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');

        // Run the add setter.
        CodeGenerator::addSetter($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('setBeans'));
    }

    #[Test]
    public function addAdderAsArray(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');

        // Run the adder.
        CodeGenerator::addAdder($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('addBean'));
    }

    #[Test]
    public function addAdderAsCollection(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');
        $property->setType(Collection::class);

        // Run the adder.
        CodeGenerator::addAdder($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('addBean'));
    }

    #[Test]
    public function addRemoverAsArray(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');

        // Run the adder.
        CodeGenerator::addRemover($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('removeBean'));
    }

    #[Test]
    public function addRemoverAsCollection(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');
        $property->setType(Collection::class);

        // Run the adder.
        CodeGenerator::addRemover($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('removeBean'));
    }

    #[Test]
    public function addHasserAsArray(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');

        // Run the adder.
        CodeGenerator::addHasser($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('hasBean'));
    }

    #[Test]
    public function addHasserAsCollection(): void
    {
        // Create values for testing.
        $class = new ClassType('TestClass');
        $property = new Property('beans');
        $property->setType(Collection::class);

        // Run the adder.
        CodeGenerator::addHasser($class, $property);

        // Make assertions.
        $this->assertTrue($class->hasMethod('hasBean'));
    }
}
