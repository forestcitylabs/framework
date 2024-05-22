<?php

namespace ForestCityLabs\Framework\Tests\Fixture\Generated\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\UuidInterface;

#[GraphQL\ObjectType(description: 'The basket holding the fruit.')]
class Basket
{
    #[GraphQL\Field]
    protected UuidInterface $id;

    #[GraphQL\Field(type: 'Fruit')]
    protected array $fruit;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFruit(): array
    {
        return $this->fruit;
    }

    public function hasFruit($fruit): bool
    {
        return in_array($fruit, $this->fruit);
    }

    public function addFruit($fruit): self
    {
        $this->fruit[] = $fruit;
        return $this;
    }

    public function removeFruit($fruit): self
    {
        unset($this->fruit[array_search($fruit, $this->fruit)]);
        return $this;
    }
}
