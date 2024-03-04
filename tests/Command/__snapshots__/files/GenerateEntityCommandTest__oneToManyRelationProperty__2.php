<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    #[ORM\Column(type: 'uuid_binary_ordered_time', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'groups')]
    private User $y;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getY(): User
    {
        return $this->y;
    }

    public function setY(User $y): self
    {
        $this->y = $y;
        return $this;
    }
}
