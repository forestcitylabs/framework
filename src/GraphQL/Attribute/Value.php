<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class Value extends AbstractType
{
    use IsDeprecableTrait;

    protected string|int|float|bool|null $value;
    protected mixed $case;

    public function __construct(
        ?string $name = null,
        ?string $description = null,
        string|int|float|bool|null $value = null,
        ?string $deprecation_reason = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->value = $value;
        $this->deprecation_reason = $deprecation_reason;
    }

    public function getValue(): string|int|float|bool|null
    {
        return $this->value;
    }

    public function setValue(string|int|float|bool|null $value): static
    {
        $this->value = $value;
        return $this;
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
