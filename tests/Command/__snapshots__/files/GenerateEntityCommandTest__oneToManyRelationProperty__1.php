<?php

namespace Application\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(targetEntity: 'Group', mappedBy: 'y')]
    private Collection $groups;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        $this->groups->add($group);
        return $this;
    }

    public function removeGroup(Group $group): self
    {
        $this->groups->removeElement($group);
        return $this;
    }

    public function hasGroup(Group $group): bool
    {
        return $this->groups->contains($group);
    }
}
