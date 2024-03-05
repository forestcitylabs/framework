<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[GraphQL\ObjectType]
#[GraphQL\InputType]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[ORM\Column(type: 'uuid_binary_ordered_time', unique: true)]
    #[GraphQL\Field]
    private UuidInterface $id;

    #[ORM\Column(unique: true)]
    #[GraphQL\Field]
    #[GraphQL\Argument]
    private string $email;

    #[ORM\Column]
    #[GraphQL\Field]
    private DateTimeImmutable $created;

    #[ORM\Column]
    #[GraphQL\Field]
    #[GraphQL\Argument]
    private string $password;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): static
    {
        $this->created = $created;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }
}
