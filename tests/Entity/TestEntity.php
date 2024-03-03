<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Entity;

use DateTimeImmutable;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use Ramsey\Uuid\UuidInterface;

#[GraphQL\ObjectType]
#[GraphQL\InputType('TestEntityInput')]
class TestEntity
{
    #[GraphQL\Field]
    private UuidInterface $id;

    #[GraphQL\Field]
    #[GraphQL\Argument(type: 'ID')]
    private AnotherTestEntity $ref;

    #[GraphQL\Field(type: 'String')]
    #[GraphQL\Argument(type: 'String')]
    private DateTimeImmutable $created;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getRef(): AnotherTestEntity
    {
        return $this->ref;
    }

    public function setRef(AnotherTestEntity $ref): static
    {
        $this->ref = $ref;
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
}
