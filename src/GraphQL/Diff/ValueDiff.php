<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\EnumValueDefinition;

class ValueDiff
{
    public function __construct(
        private readonly EnumValueDefinition $old_value,
        private readonly EnumValueDefinition $new_value
    ) {
    }

    public function getOldValue(): EnumValueDefinition
    {
        return $this->old_value;
    }

    public function getNewValue(): EnumValueDefinition
    {
        return $this->new_value;
    }

    public function isNameDifferent(): bool
    {
        return $this->old_value->name !== $this->new_value->name;
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_value->description !== $this->new_value->description;
    }

    public function isDeprecationReasonDifferent(): bool
    {
        return $this->old_value->deprecationReason !== $this->new_value->deprecationReason;
    }

    public function isValueDifferent(): bool
    {
        return $this->old_value->value !== $this->new_value->value;
    }

    public function isDifferent(): bool
    {
        return ($this->isNameDifferent()
            || $this->isDescriptionDifferent()
            || $this->isDeprecationReasonDifferent()
            || $this->isValueDifferent());
    }
}
