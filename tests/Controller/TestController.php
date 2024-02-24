<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Controller;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Routing\Attribute as Route;

#[Route\RoutePrefix("/beans")]
class TestController
{
    #[GraphQL\Query]
    #[GraphQL\ObjectField(type: 'TestEntity')]
    public function testEntities(
        #[GraphQL\Argument] ?string $created = null
    ): array {
        return [];
    }

    #[Route\Route("/test")]
    public function test()
    {
        return null;
    }
}
