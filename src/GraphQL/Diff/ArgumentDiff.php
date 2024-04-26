<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

class ArgumentDiff
{
    public function __construct(
        private readonly Argument $old_argument,
        private readonly Argument $new_argument
    ) {
    }

    public function getOldArgument(): Argument
    {
        return $this->old_argument;
    }

    public function getNewArgument(): Argument
    {
        return $this->new_argument;
    }

    public function isNameDifferent(): bool
    {
        return $this->old_argument->name !== $this->new_argument->name;
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_argument->description !== $this->new_argument->description;
    }
    public function isTypeDifferent(): bool
    {
        $old_type = Type::getNamedType($this->old_argument->getType());
        $new_type = Type::getNamedType($this->new_argument->getType());
        return $old_type->name !== $new_type->name;
    }

    public function isListDifferent(): bool
    {
        $old_list = false;
        $new_list = false;
        $old_type = $this->old_argument->getType();
        $new_type = $this->new_argument->getType();
        while ($old_type instanceof WrappingType) {
            if ($old_type instanceof ListOfType) {
                $old_list = true;
            }
            $old_type = $old_type->getWrappedType();
        }
        while ($new_type instanceof WrappingType) {
            if ($new_type instanceof ListOfType) {
                $new_list = true;
            }
            $new_type = $new_type->getWrappedType();
        }
        return $new_list !== $old_list;
    }

    public function isNonNullDifferent(): bool
    {
        $old_non_null = false;
        $new_non_null = false;
        $old_type = $this->old_argument->getType();
        $new_type = $this->new_argument->getType();
        if ($old_type instanceof NonNull) {
            $old_non_null = true;
        }
        if ($new_type instanceof NonNull) {
            $new_non_null = true;
        }
        return $new_non_null !== $old_non_null;
    }

    public function isDifferent(): bool
    {
        return ($this->isNameDifferent()
            || $this->isDescriptionDifferent()
            || $this->isTypeDifferent()
            || $this->isListDifferent()
            || $this->isNonNullDifferent());
    }
}
