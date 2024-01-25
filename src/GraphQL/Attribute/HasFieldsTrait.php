<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

trait HasFieldsTrait
{
    protected array $fields = [];

    public function addField(Field $field): self
    {
        $this->fields[$field->getName()] = $field;
        return $this;
    }

    public function getField(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
