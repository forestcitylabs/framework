<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;

class FieldDiff
{
    public function __construct(
        private readonly FieldDefinition $old_field,
        private readonly FieldDefinition $new_field,
        private readonly array $new_arguments,
        private readonly array $altered_arguments,
        private readonly array $dropped_arguments
    ) {
    }

    public function getOldField(): FieldDefinition
    {
        return $this->old_field;
    }

    public function getNewField(): FieldDefinition
    {
        return $this->new_field;
    }

    public function getNewArguments(): array
    {
        return $this->new_arguments;
    }

    public function getAlteredArguments(): array
    {
        return $this->altered_arguments;
    }

    public function getDroppedArguments(): array
    {
        return $this->dropped_arguments;
    }

    public function isNameDifferent(): bool
    {
        return $this->old_field->getName() !== $this->new_field->getName();
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_field->description !== $this->new_field->description;
    }

    public function isTypeDifferent(): bool
    {
        $old_type = Type::getNamedType($this->old_field->getType());
        $new_type = Type::getNamedType($this->new_field->getType());
        return $old_type->name !== $new_type->name;
    }

    public function isListDifferent(): bool
    {
        $old_list = false;
        $new_list = false;
        $old_type = $this->old_field->getType();
        $new_type = $this->new_field->getType();
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
        $old_type = $this->old_field->getType();
        $new_type = $this->new_field->getType();
        if ($old_type instanceof NonNull) {
            $old_non_null = true;
        }
        if ($new_type instanceof NonNull) {
            $new_non_null = true;
        }
        return $new_non_null !== $old_non_null;
    }

    public function isDeprecationReasonDifferent(): bool
    {
        return $this->old_field->deprecationReason !== $this->new_field->deprecationReason;
    }

    public function isDifferent(): bool
    {
        return ($this->isNameDifferent()
            || $this->isDescriptionDifferent()
            || $this->isDeprecationReasonDifferent()
            || $this->isTypeDifferent()
            || $this->isListDifferent()
            || $this->isNonNullDifferent()
            || count($this->new_arguments) > 0
            || count($this->altered_arguments) > 0
            || count($this->dropped_arguments) > 0);
    }
}
