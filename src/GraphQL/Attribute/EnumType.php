<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;
use ForestCityLabs\Framework\Utility\SerializerTrait;

#[Attribute(Attribute::TARGET_CLASS)]
class EnumType extends AbstractType
{
    use SerializerTrait;

    protected array $values = [];

    public function addValue(Value $value): static
    {
        $this->values[$value->getName()] = $value;
        return $this;
    }

    public function getValue(string $name): ?Value
    {
        return $this->values[$name] ?? null;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
