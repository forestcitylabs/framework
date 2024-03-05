<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Attribute;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Value::class)]
#[CoversClass(AbstractType::class)]
#[Group("graphql")]
class ValueTest extends TestCase
{
    #[Test]
    public function value(): void
    {
        $value = new Value("name", 'description', 'value');
        $this->assertEquals('name', $value->getName());
        $this->assertEquals('description', $value->getDescription());
        $this->assertEquals('value', $value->getValue());
        $this->assertEquals(null, $value->getDeprecationReason());

        $value->setName('other_name');
        $this->assertEquals('other_name', $value->getName());

        $value->setDescription('other_description');
        $this->assertEquals('other_description', $value->getDescription());

        $value->setValue('other_value');
        $this->assertEquals('other_value', $value->getValue());

        $value->setDeprecationReason('deprecation_reason');
        $this->assertEquals('deprecation_reason', $value->getDeprecationReason());

        $value->setClassName(Value::class);
        $this->assertEquals(Value::class, $value->getClassName());
    }
}
