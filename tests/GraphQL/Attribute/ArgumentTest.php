<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Attribute;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Argument::class)]
#[CoversClass(AbstractType::class)]
#[Group("graphql")]
class ArgumentTest extends TestCase
{
    #[Test]
    public function argument(): void
    {
        $argument = new Argument('name', 'description', 'Type', true, false);
        $this->assertEquals('name', $argument->getName());
        $this->assertEquals('description', $argument->getDescription());
        $this->assertEquals('Type', $argument->getType());
        $this->assertEquals(true, $argument->getList());
        $this->assertEquals(false, $argument->getNotNull());

        $argument->setName('other_name');
        $this->assertEquals('other_name', $argument->getName());

        $argument->setDescription('other_description');
        $this->assertEquals('other_description', $argument->getDescription());

        $argument->setType('OtherType');
        $this->assertEquals('OtherType', $argument->getType());

        $argument->setList(false);
        $this->assertEquals(false, $argument->getList());

        $argument->setNotNull(true);
        $this->assertEquals(true, $argument->getNotNull());

        $argument->setClassName(Argument::class);
        $this->assertEquals(Argument::class, $argument->getClassName());

        $argument->setAttributeType(Argument::TYPE_PROPERTY);
        $this->assertEquals(Argument::TYPE_PROPERTY, $argument->getAttributeType());

        $argument->setAttributeName('arg_name');
        $this->assertEquals('arg_name', $argument->getAttributeName());
    }
}
