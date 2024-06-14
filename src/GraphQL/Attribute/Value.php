<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Value extends AbstractType
{
    use IsDeprecableTrait;

    protected mixed $case;

    public function __construct(
        ?string $name = null,
        ?string $description = null,
        ?string $deprecation_reason = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->deprecation_reason = $deprecation_reason;
    }

    public function setCase(mixed $case): static
    {
        $this->case = $case;
        return $this;
    }

    public function getCase(): mixed
    {
        return $this->case;
    }
}
