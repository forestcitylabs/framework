<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[ORM\Column(type: 'uuid_binary_ordered_time', unique: true)]
    private Uuid $id;

    #[ORM\Column]
    private array $roles = [];

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function addRole(string $role): self
    {
        $this->roles[] = $role;
        return $this;
    }

    public function removeRole(string $role): self
    {
        unset($this->roles[array_search($role, $this->roles)]);
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }
}
