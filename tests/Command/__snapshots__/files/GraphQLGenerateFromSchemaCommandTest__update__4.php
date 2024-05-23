<?php

namespace ForestCityLabs\Framework\Tests\Fixture\Generated\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\UuidInterface;

#[GraphQL\InterfaceType]
abstract class Fruit
{
    #[GraphQL\Field]
    protected UuidInterface $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }
}
