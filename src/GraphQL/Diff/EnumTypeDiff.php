<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\EnumType;

class EnumTypeDiff
{
    public function __construct(
        private readonly EnumType $old_enum,
        private readonly EnumType $new_enum,
        private readonly array $new_values,
        private readonly array $altered_values,
        private readonly array $dropped_values
    ) {
    }

    public function isNameDifferent(): bool
    {
        return $this->old_enum->name() !== $this->new_enum->name();
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_enum->description() !== $this->new_enum->description();
    }

    public function getOldEnum(): EnumType
    {
        return $this->old_enum;
    }

    public function getNewEnum(): EnumType
    {
        return $this->new_enum;
    }

    public function getNewValues(): array
    {
        return $this->new_values;
    }

    public function getAlteredValues(): array
    {
        return $this->altered_values;
    }

    public function getDroppedValues(): array
    {
        return $this->dropped_values;
    }

    public function isDifferent(): bool
    {
        return ($this->isNameDifferent()
            || $this->isDescriptionDifferent()
            || count($this->new_values) > 0
            || count($this->altered_values) > 0
            || count($this->dropped_values) > 0);
    }
}
