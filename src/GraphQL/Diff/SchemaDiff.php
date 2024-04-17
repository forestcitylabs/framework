<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\NamedType;
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

    /**
     * @return array<NamedType>
     */
    public function getNewTypes(): array
    {
        return array_merge($this->new_interfaces, $this->new_types, $this->new_enums, $this->new_inputs);
    }

    /**
     * @return array<TypeDiff>
     */
    public function getAlteredTypes(): array
    {
        return array_merge($this->altered_interfaces, $this->altered_types, $this->altered_enums, $this->altered_inputs);
    }

    /**
     * @return array<NamedType>
     */
    public function getDroppedTypes(): array
    {
        return array_merge($this->dropped_interfaces, $this->dropped_types, $this->dropped_enums, $this->dropped_inputs);
    }

    public function getNewObjectTypes(): array
    {
        return $this->new_types;
    }

    public function getAlteredObjectTypes(): array
    {
        return $this->altered_types;
    }

    public function getDroppedObjectTypes(): array
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
