<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Attribute;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\EnumType;
use ForestCityLabs\Framework\GraphQL\Attribute\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnumType::class)]
#[CoversClass(AbstractType::class)]
#[UsesClass(Value::class)]
#[Group("graphql")]
class EnumTypeTest extends TestCase
{
    #[Test]
    public function enumType(): void
    {
        $enum = new EnumType("name", "description");
        $this->assertEquals("name", $enum->getName());
        $this->assertEquals("description", $enum->getDescription());

        $enum->setName("other_name");
        $this->assertEquals("other_name", $enum->getName());

        $enum->setDescription("other_description");
        $this->assertEquals("other_description", $enum->getDescription());

        $value = new Value("test");
        $enum->addValue($value);
        $this->assertEquals($value, $enum->getValue("test"));
        $enum->addValue(new Value());
        $this->assertEquals(2, count($enum->getValues()));
    }
}
