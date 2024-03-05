<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

class TypeDiff
{
    private bool $is_different = false;
    private ?string $from_name = null;
    private ?string $to_name = null;
    private ?bool $from_non_null = null;
    private ?bool $to_non_null = null;
    private ?bool $from_list = null;
    private ?bool $to_list = null;
    private ?bool $from_has_fields = null;
    private ?bool $to_has_fields = null;
    private array $field_diffs = [];

    public function addFieldDiff(FieldDiff $diff): static
    {
        $this->field_diffs[] = $diff;
        return $this;
    }

    public function getFieldDiffs(): array
    {
        return $this->field_diffs;
    }

    public function setNameDiff(?string $from, ?string $to): static
    {
        if ($from !== $to) {
            $this->is_different = true;
        }
        $this->from_name = $from;
        $this->to_name = $to;
        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->from_name;
    }

    public function getToName(): ?string
    {
        return $this->to_name;
    }

    public function setNonNullDiff(?bool $from, ?bool $to): static
    {
        if ($from !== $to) {
            $this->is_different = true;
        }
        $this->from_non_null = $from;
        $this->to_non_null = $to;
        return $this;
    }

    public function getFromNonNull(): ?bool
    {
        return $this->from_non_null;
    }

    public function getToNonNull(): ?bool
    {
        return $this->to_non_null;
    }

    public function setListDiff(?bool $from, ?bool $to): static
    {
        if ($from !== $to) {
            $this->is_different = true;
        }
        $this->from_list = $from;
        $this->to_list = $to;
        return $this;
    }

    public function getFromList(): ?bool
    {
        return $this->from_list;
    }

    public function getToList(): ?bool
    {
        return $this->to_list;
    }

    public function setHasFieldsDiff(?bool $from, ?bool $to): static
    {
        if ($from !== $to) {
            $this->is_different = true;
        }
        $this->from_has_fields = $from;
        $this->to_has_fields = $to;
        return $this;
    }

    public function getFromHasFields(): ?bool
    {
        return $this->from_has_fields;
    }

    public function getToHasFiels(): ?bool
    {
        return $this->to_has_fields;
    }

    public function isDifferent(): bool
    {
        if ($this->is_different) {
            return true;
        }

        foreach ($this->field_diffs as $diff) {
            if ($diff->isDifferent()) {
                return true;
            }
        }
        return false;
    }
}
