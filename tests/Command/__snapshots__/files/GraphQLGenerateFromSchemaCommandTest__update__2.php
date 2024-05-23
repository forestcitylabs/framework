<?php

namespace ForestCityLabs\Framework\Tests\Fixture\Generated\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\UuidInterface;

#[GraphQL\ObjectType]
#[GraphQL\InputType(name: 'AppleInput')]
class Apple extends Fruit
{
    #[GraphQL\Field]
    protected UuidInterface $id;

    #[GraphQL\Field]
    #[GraphQL\Argument]
    protected ?string $note;

    #[GraphQL\Argument]
    protected AppleType $type;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getType(): AppleType
    {
        return $this->type;
    }

    public function setType(AppleType $type): self
    {
        $this->type = $type;
        return $this;
    }
}
