<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\InputObjectType;

class InputObjectTypeDiff
{
    public function __construct(
        private readonly InputObjectType $old_input,
        private readonly InputObjectType $new_input,
        private readonly array $new_fields,
        private readonly array $altered_fields,
        private readonly array $dropped_fields
    ) {
    }

    public function isNameDifferent(): bool
    {
        return $this->old_input->name() !== $this->new_input->name();
    }

    public function isDescriptionDifferent(): bool
    {
        return $this->old_input->description() !== $this->new_input->description();
    }

    public function getOldInput(): InputObjectType
    {
        return $this->old_input;
    }

    public function getNewInput(): InputObjectType
    {
        return $this->new_input;
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
            || count($this->dropped_fields) > 0);
    }
}
