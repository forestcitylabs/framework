<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Diff;

use ForestCityLabs\Framework\GraphQL\Diff\ValueDiff;
use GraphQL\Type\Definition\EnumValueDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as FrameworkTestCase;

#[CoversClass(ValueDiff::class)]
#[Group('graphql')]
class ValueDiffTest extends FrameworkTestCase
{
    #[Test]
    public function differentValues(): void
    {
        $old_value = new EnumValueDefinition([
            'name' => 'OLD_VALUE',
            'description' => 'This is the old description.',
            'value' => 0,
        ]);
        $new_value = new EnumValueDefinition([
            'name' => 'NEW_VALUE',
            'description' => 'This is the new description.',
            'deprecationReason' => 'Don\'t use this',
            'value' => 1,
        ]);
        $diff = new ValueDiff($old_value, $new_value);

        $this->assertTrue($diff->isDifferent());
        $this->assertTrue($diff->isDeprecationReasonDifferent());
        $this->assertTrue($diff->isDescriptionDifferent());
        $this->assertTrue($diff->isValueDifferent());
        $this->assertSame($diff->getOldValue(), $old_value);
        $this->assertSame($diff->getNewValue(), $new_value);
    }

    #[Test]
    public function sameValues(): void
    {
        $old_value = new EnumValueDefinition([
            'name' => 'VALUE',
        ]);
        $new_value = new EnumValueDefinition([
            'name' => 'VALUE',
        ]);
        $diff = new ValueDiff($old_value, $new_value);
        $this->assertFalse($diff->isDifferent());
    }
}
