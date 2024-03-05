<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[GraphQL\ObjectType]
#[GraphQL\InputType]
class Basket
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[ORM\Column(type: 'uuid_binary_ordered_time', unique: true)]
    #[GraphQL\Field]
    private UuidInterface $id;

    #[ORM\OneToMany(targetEntity: Apple::class, mappedBy: 'basket')]
    #[GraphQL\Field]
    private Collection $apples;

    public function __construct()
    {
        $this->apples = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getApples(): Collection
    {
        return $this->apples;
    }

    public function addApple(Apple $apple): static
    {
        $this->apples->add($apple);
        return $this;
    }

    public function removeApple(Apple $apple): static
    {
        $this->apples->removeElement($apple);
        return $this;
    }

    public function hasApple(Apple $apple): bool
    {
        return $this->apples->contains($apple);
    }
}
