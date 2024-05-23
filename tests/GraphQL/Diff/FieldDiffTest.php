<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Diff;

use ForestCityLabs\Framework\GraphQL\Diff\FieldDiff;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldDiff::class)]
#[Group('graphql')]
class FieldDiffTest extends TestCase
{
    #[Test]
    public function compareSame(): void
    {
        $old = new FieldDefinition([
            'name' => 'same',
            'description' => 'This is the same.',
            'type' => Type::string(),
            'args' => [],
        ]);
        $new = new FieldDefinition([
            'name' => 'same',
            'description' => 'This is the same.',
            'type' => Type::string(),
            'args' => [],
        ]);
        $diff = new FieldDiff($old, $new, [], [], []);
        $this->assertSame($old, $diff->getOldField());
        $this->assertSame($new, $diff->getNewField());
        $this->assertEmpty($diff->getDroppedArguments());
        $this->assertEmpty($diff->getAlteredArguments());
        $this->assertEmpty($diff->getNewArguments());
        $this->assertFalse($diff->isDifferent());
        $this->assertFalse($diff->isNameDifferent());
        $this->assertFalse($diff->isDescriptionDifferent());
        $this->assertFalse($diff->isDeprecationReasonDifferent());
        $this->assertFalse($diff->isTypeDifferent());
    }

    #[Test]
    public function compareDifferent(): void
    {
        $old = new FieldDefinition([
            'name' => 'new',
            'type' => Type::nonNull(Type::listOf(Type::string())),
        ]);
        $new = new FieldDefinition([
            'name' => 'old',
            'type' => Type::nonNull(Type::listOf(Type::int())),
        ]);
        $diff = new FieldDiff($old, $new, [], [], []);
        $this->assertTrue($diff->isDifferent());
        $this->assertFalse($diff->isListDifferent());
        $this->assertFalse($diff->isNonNullDifferent());
        $this->assertTrue($diff->isTypeDifferent());
    }
}
