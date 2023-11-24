<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Controller;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

class TestController
{
    #[GraphQL\Query]
    #[GraphQL\ObjectField(type: 'TestEntity')]
    public function testEntities(
        #[GraphQL\Argument] ?string $created = null
    ): array {
        return [];
    }
}
