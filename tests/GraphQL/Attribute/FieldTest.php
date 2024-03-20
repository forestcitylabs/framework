<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Attribute;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Field::class)]
#[CoversClass(AbstractType::class)]
#[UsesClass(Argument::class)]
#[Group("graphql")]
class FieldTest extends TestCase
{
    #[Test]
    public function field(): void
    {
        $field = new Field("name", 'description', 'Type', true, false);
        $this->assertEquals('name', $field->getName());
        $this->assertEquals('description', $field->getDescription());
        $this->assertEquals('Type', $field->getType());
        $this->assertEquals(true, $field->getList());
        $this->assertEquals(false, $field->getNotNull());
        $this->assertEquals(null, $field->getDeprecationReason());

        $field->setName('other_name');
        $this->assertEquals('other_name', $field->getName());

        $field->setDescription('other_description');
        $this->assertEquals('other_description', $field->getDescription());

        $field->setType('OtherType');
        $this->assertEquals('OtherType', $field->getType());

        $field->setList(false);
        $this->assertEquals(false, $field->getList());

        $field->setNotNull(true);
        $this->assertEquals(true, $field->getNotNull());

        $field->setDeprecationReason('deprecation_reason');
        $this->assertEquals('deprecation_reason', $field->getDeprecationReason());

        $field->setClassName(Field::class);
        $this->assertEquals(Field::class, $field->getClassName());

        $field->setAttributeType(Field::TYPE_PROPERTY);
        $this->assertEquals(Field::TYPE_PROPERTY, $field->getAttributeType());

        $field->setAttributeName('arg_name');
        $this->assertEquals('arg_name', $field->getAttributeName());

        $arg = new Argument('test');
        $field->addArgument($arg);
        $this->assertEquals($arg, $field->getArgument('test'));
        $field->addArgument(new Argument());
        $this->assertEquals(2, count($field->getArguments()));
    }
}
