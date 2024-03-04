<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Controller;

use DateTime;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Routing\Attribute as Route;
use Ramsey\Uuid\UuidInterface;

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

    public function uuidParameter(
        UuidInterface $uuid,
        string $beans = 'string',
        $train = 'beans',
        int|bool $check = false
    ): void {
    }

    public function dateTimeParameter(
        DateTime $date_time,
        string $beans,
        $train,
        int|bool $check
    ): void {
    }

    public function serviceParameter(
        TestController $controller,
        int $integer = 0,
        $untyped = true,
        int|bool $union = false,
        string $test = "string"
    ): void {
    }

    public function __invoke()
    {
        return "test";
    }
}
