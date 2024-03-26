<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

class FieldDiff
{
    private ?string $from_name = null;
    private ?string $to_name = null;
    private array $argument_diffs = [];
    private ?TypeDiff $type_diff = null;
    private bool $is_different = false;

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

    public function setTypeDiff(TypeDiff $diff): static
    {
        $this->type_diff = $diff;
        return $this;
    }

    public function getTypeDiff(): TypeDiff
    {
        return $this->type_diff;
    }

    public function addArgumentDiff(ArgumentDiff $diff): static
    {
        $this->argument_diffs[] = $diff;
        return $this;
    }

    public function getArgumentDiffs(): array
    {
        return $this->argument_diffs;
    }

    public function isDifferent(): bool
    {
        if ($this->is_different) {
            return true;
        }

        if ($this->type_diff->isDifferent()) {
            return true;
        }

        foreach ($this->argument_diffs as $diff) {
            if ($diff->isDifferent()) {
                return true;
            }
        }
        return false;
    }
}
