<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NamedType;

class InterfaceTypeDiff implements TypeDiff
{
    public function __construct(
        private readonly InterfaceType $old_interface,
        private readonly InterfaceType $new_interface,
        private readonly array $new_fields,
        private readonly array $altered_fields,
        private readonly array $dropped_fields
    ) {
    }

    public function isNameDifferent(): bool
    {
        return $this->old_interface->name() !== $this->new_interface->name();
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_interface->description() !== $this->new_interface->description();
    }

    public function getOldType(): NamedType
    {
        return $this->old_interface;
    }

    public function getNewType(): NamedType
    {
        return $this->new_interface;
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
