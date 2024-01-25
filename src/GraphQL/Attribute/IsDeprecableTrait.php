<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Attribute;

trait IsDeprecableTrait
{
    protected ?string $deprecation_reason;

    public function getDeprecationReason(): ?string
    {
        return $this->deprecation_reason;
    }

    public function setDeprecationReason(string $deprecation_reason): static
    {
        $this->deprecation_reason = $deprecation_reason;
        return $this;
    }
}
