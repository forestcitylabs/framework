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
    #[GraphQL\ObjectField]
    private UuidInterface $id;

    #[GraphQL\ObjectField]
    #[GraphQL\InputField(type: 'ID')]
    private AnotherTestEntity $ref;

    #[GraphQL\ObjectField(type: 'String')]
    #[GraphQL\InputField(type: 'String')]
    private DateTimeImmutable $created;
}
