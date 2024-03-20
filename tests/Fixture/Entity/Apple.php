<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[GraphQL\ObjectType]
#[GraphQL\InputType("AppleInput")]
class Apple extends Fruit
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[ORM\Column(type: 'uuid_binary_ordered_time', unique: true)]
    #[GraphQL\Field]
    private UuidInterface $id;

    #[ORM\Column]
    #[GraphQL\Field]
    #[GraphQL\Argument]
    private AppleTypeEnum $type;

    #[ORM\ManyToOne(targetEntity: Basket::class, inversedBy: 'apples')]
    #[GraphQL\Field]
    private Basket $basket;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getType(): AppleTypeEnum
    {
        return $this->type;
    }

    public function setType(AppleTypeEnum $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getBasket(): Basket
    {
        return $this->basket;
    }

    public function setBasket(Basket $basket): static
    {
        $this->basket = $basket;
        return $this;
    }
}
