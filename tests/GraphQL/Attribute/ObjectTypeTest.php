<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Attribute;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectType::class)]
#[CoversClass(AbstractType::class)]
#[UsesClass(Field::class)]
#[Group("graphql")]
class ObjectTypeTest extends TestCase
{
    #[Test]
    public function objectType(): void
    {
        $object = new ObjectType('name', 'description');
        $this->assertEquals('name', $object->getName());
        $this->assertEquals('description', $object->getDescription());

        $object->setName('other_name');
        $this->assertEquals('other_name', $object->getName());

        $object->setDescription('other_description');
        $this->assertEquals('other_description', $object->getDescription());

        $object->setClassName(ObjectType::class);
        $this->assertEquals(ObjectType::class, $object->getClassName());

        $object->addInterface("interface");
        $this->assertEquals("interface", $object->getInterface("interface"));
        $this->assertEquals(null, $object->getInterface("nope"));
        $this->assertEquals(1, count($object->getInterfaces()));

        $field = new Field("field");
        $object->addField($field);
        $this->assertEquals($field, $object->getField("field"));
        $this->assertEquals(1, count($object->getFields()));
    }
}
