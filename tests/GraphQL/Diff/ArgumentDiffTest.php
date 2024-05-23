<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Diff;

use ForestCityLabs\Framework\GraphQL\Diff\ArgumentDiff;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('graphql')]
#[CoversClass(ArgumentDiff::class)]
class ArgumentDiffTest extends TestCase
{
    #[Test]
    public function differentArgumentDiff(): void
    {
        $old_arg = new Argument([
            'name' => 'old_arg',
            'description' => 'This is the old one.',
            'type' => Type::listOf(Type::string()),
        ]);
        $new_arg = new Argument([
            'name' => 'new_arg',
            'description' => 'This is the new one.',
            'type' => Type::nonNull(Type::string()),
        ]);

        $diff = new ArgumentDiff($old_arg, $new_arg);
        $this->assertTrue($diff->isDifferent());
        $this->assertTrue($diff->isNameDifferent());
        $this->assertTrue($diff->isDescriptionDifferent());
        $this->assertFalse($diff->isTypeDifferent());
        $this->assertTrue($diff->isListDifferent());
        $this->assertTrue($diff->isNonNullDifferent());
        $this->assertSame($diff->getOldArgument(), $old_arg);
        $this->assertSame($diff->getNewArgument(), $new_arg);
    }

    #[Test]
    public function sameArgumentDiff(): void
    {
        $old_arg = new Argument([
            'name' => 'arg',
            'description' => 'This is the arg.',
            'type' => Type::nonNull(Type::listOf(Type::string())),
        ]);
        $new_arg = new Argument([
            'name' => 'arg',
            'description' => 'This is the arg.',
            'type' => Type::nonNull(Type::listOf(Type::string())),
        ]);
        $diff = new ArgumentDiff($old_arg, $new_arg);
        $this->assertFalse($diff->isDifferent());
    }
}
