<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Diff;

use ForestCityLabs\Framework\GraphQL\Diff\EnumTypeDiff;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnumTypeDiff::class)]
#[Group('graphql')]
class EnumTypeDiffTest extends TestCase
{
    #[Test]
    public function sameEnum(): void
    {
        $old_enum = new EnumType([
            'name' => 'AppleType',
            'description' => 'This is the description',
            'deprecationReason' => 'It is deprecated.',
            'values' => [
                new EnumValueDefinition(['name' => 'VALUE_ONE']),
                new EnumValueDefinition(['name' => 'VALUE_TWO']),
            ],
        ]);
        $new_enum = new EnumType([
            'name' => 'AppleType',
            'description' => 'This is the description',
            'deprecationReason' => 'It is deprecated.',
            'values' => [
                new EnumValueDefinition(['name' => 'VALUE_ONE']),
                new EnumValueDefinition(['name' => 'VALUE_TWO']),
            ],
        ]);
        $diff = new EnumTypeDiff($old_enum, $new_enum, [], [], []);
        $this->assertFalse($diff->isDifferent());
        $this->assertSame($old_enum, $diff->getOldType());
        $this->assertSame($new_enum, $diff->getNewType());
        $this->assertEquals($diff->getAlteredValues(), []);
        $this->assertEquals($diff->getDroppedValues(), []);
        $this->assertEquals($diff->getNewValues(), []);
    }
}
