<?php

namespace ForestCityLabs\Framework\Tests\Fixture\Generated\Controller;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Tests\Fixture\Generated\Entity\Apple;
use ForestCityLabs\Framework\Tests\Fixture\Generated\Entity\Basket;
use Ramsey\Uuid\UuidInterface;

class GraphQLController
{
    #[GraphQL\Field(type: 'Apple')]
    #[GraphQL\Query]
    public function getApples(): array
    {
    }

    #[GraphQL\Field]
    #[GraphQL\Query]
    public function getBasket(
        #[GraphQL\Argument]
        UuidInterface $id,
    ): Basket {
    }

    #[GraphQL\Field]
    #[GraphQL\Mutation]
    public function createApple(
        #[GraphQL\Argument]
        Apple $apple,
    ): Apple {
    }
}
