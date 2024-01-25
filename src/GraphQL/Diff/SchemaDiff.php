<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

class SchemaDiff
{
    private array $type_diffs = [];

    public function addTypeDiff(TypeDiff $diff): static
    {
        $this->type_diffs[] = $diff;
        return $this;
    }

    public function getTypeDiffs(): array
    {
        return $this->type_diffs;
    }

    public function isDifferent(): bool
    {
        foreach ($this->type_diffs as $type_diff) {
            if ($type_diff->isDifferent()) {
                return true;
            }
        }
        return false;
    }
}
