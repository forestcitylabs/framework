<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Schema;

class SchemaDiff
{
    public function __construct(
        private readonly Schema $old_schema,
        private readonly Schema $new_schema,
        private readonly array $new_types,
        private readonly array $altered_types,
        private readonly array $dropped_types,
        private readonly array $new_inputs,
        private readonly array $altered_inputs,
        private readonly array $dropped_inputs,
        private readonly array $new_interfaces,
        private readonly array $altered_interfaces,
        private readonly array $dropped_interfaces,
        private readonly array $new_enums,
        private readonly array $altered_enums,
        private readonly array $dropped_enums
    ) {
    }

    public function getOldSchema(): Schema
    {
        return $this->old_schema;
    }

    public function getNewSchema(): Schema
    {
        return $this->new_schema;
    }

    public function getNewTypes(): array
    {
        return $this->new_types;
    }

    public function getAlteredTypes(): array
    {
        return $this->altered_types;
    }

    public function getDroppedTypes(): array
    {
        return $this->dropped_types;
    }

    public function getNewInputs(): array
    {
        return $this->new_inputs;
    }

    public function getAlteredInputs(): array
    {
        return $this->altered_inputs;
    }

    public function getDroppedInputs(): array
    {
        return $this->altered_inputs;
    }

    public function getNewInterfaces(): array
    {
        return $this->new_interfaces;
    }

    public function getAlteredInterfaces(): array
    {
        return $this->altered_interfaces;
    }

    public function getDroppedInterfaces(): array
    {
        return $this->dropped_interfaces;
    }

    public function getNewEnums(): array
    {
        return $this->new_enums;
    }

    public function getAlteredEnums(): array
    {
        return $this->altered_enums;
    }

    public function getDroppedEnums(): array
    {
        return $this->dropped_enums;
    }

    public function isDifferent(): bool
    {
        return (count($this->new_types) > 0
            || count($this->altered_types) > 0
            || count($this->dropped_types) > 0
            || count($this->new_inputs) > 0
            || count($this->altered_inputs) > 0
            || count($this->dropped_inputs) > 0
            || count($this->new_interfaces) > 0
            || count($this->altered_interfaces) > 0
            || count($this->dropped_interfaces) > 0
            || count($this->new_enums) > 0
            || count($this->altered_enums) > 0
            || count($this->dropped_enums) > 0);
    }
}
