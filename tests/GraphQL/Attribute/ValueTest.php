<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Attribute;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Value;
use ForestCityLabs\Framework\Tests\Fixture\Entity\AppleTypeEnum;
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
        $value = new Value("name", 'description');
        $this->assertEquals('name', $value->getName());
        $this->assertEquals('description', $value->getDescription());
        $this->assertEquals(null, $value->getDeprecationReason());

        $value->setName('other_name');
        $this->assertEquals('other_name', $value->getName());

        $value->setDescription('other_description');
        $this->assertEquals('other_description', $value->getDescription());

        $value->setDeprecationReason('deprecation_reason');
        $this->assertEquals('deprecation_reason', $value->getDeprecationReason());

        $value->setClassName(Value::class);
        $this->assertEquals(Value::class, $value->getClassName());

        $value->setCase(AppleTypeEnum::Macintosh);
        $this->assertEquals(AppleTypeEnum::Macintosh, $value->getCase());
    }
}
