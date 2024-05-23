<?php

namespace ForestCityLabs\Framework\Tests\Fixture\Generated\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\UuidInterface;

#[GraphQL\ObjectType]
class Basket
{
    #[GraphQL\Field]
    protected UuidInterface $id;

    #[GraphQL\Field(type: 'Fruit')]
    protected array $items;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function hasItem($item): bool
    {
        return in_array($item, $this->items);
    }

    public function addItem($item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function removeItem($item): self
    {
        unset($this->items[array_search($item, $this->items)]);
        return $this;
    }
}
