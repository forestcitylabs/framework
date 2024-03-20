<?php

namespace Application\Entity;

use DateTimeImmutable;
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

    #[ORM\Column(unique: true)]
    private string $email;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $birthday = null;

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

    #[ORM\Column(unique: true)]
    private bool $consent;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(nullable: true)]
    private ?float $exchange = null;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getBirthday(): ?DateTimeImmutable
    {
        return $this->birthday;
    }

    public function setBirthday(?DateTimeImmutable $birthday): self
    {
        $this->birthday = $birthday;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): self
    {
        $this->age = $age;
        return $this;
    }

    public function getConsent(): bool
    {
        return $this->consent;
    }

    public function setConsent(bool $consent): self
    {
        $this->consent = $consent;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getExchange(): ?float
    {
        return $this->exchange;
    }

    public function setExchange(?float $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }
}
