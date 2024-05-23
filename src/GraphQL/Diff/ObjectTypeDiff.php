<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;

class ObjectTypeDiff implements TypeDiff
{
    public function __construct(
        private readonly ObjectType $old_type,
        private readonly ObjectType $new_type,
        private readonly array $new_fields,
        private readonly array $altered_fields,
        private readonly array $dropped_fields
    ) {
    }

    public function isNameDifferent(): bool
    {
        return $this->old_type->name() !== $this->new_type->name();
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_type->description() !== $this->new_type->description();
    }

    public function getOldType(): NamedType
    {
        return $this->old_type;
    }

    public function getNewType(): NamedType
    {
        return $this->new_type;
    }

    public function getNewFields(): array
    {
        return $this->new_fields;
    }

    public function getAlteredFields(): array
    {
        return $this->altered_fields;
    }

    public function getDroppedFields(): array
    {
        return $this->dropped_fields;
    }

    public function isDifferent(): bool
    {
        return ($this->isNameDifferent()
            || $this->isDescriptionDifferent()
            || count($this->new_fields) > 0
            || count($this->altered_fields) > 0
            || count($this->dropped_fields) > 0
        );
    }
}
