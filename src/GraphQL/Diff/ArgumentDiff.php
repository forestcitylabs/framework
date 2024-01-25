<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

class ArgumentDiff
{
    private ?string $from_name = null;
    private ?string $to_name = null;
    private TypeDiff $type_diff;
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

    public function isDifferent(): bool
    {
        if ($this->is_different) {
            return true;
        }

        if ($this->type_diff->isDifferent()) {
            return true;
        }

        return false;
    }
}
