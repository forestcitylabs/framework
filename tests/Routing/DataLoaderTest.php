<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Routing;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use ForestCityLabs\Framework\Routing\Attribute\Route;
use ForestCityLabs\Framework\Routing\Collection\RouteCollection;
use ForestCityLabs\Framework\Routing\DataLoader;
use ForestCityLabs\Framework\Routing\MetadataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

#[CoversClass(DataLoader::class)]
#[UsesClass(RouteCollector::class)]
#[UsesClass(Route::class)]
#[UsesClass(Std::class)]
#[UsesClass(GroupCountBased::class)]
#[UsesClass(RouteCollection::class)]
class DataLoaderTest extends TestCase
{
    #[Test]
    public function uncachedRoutes()
    {
        $item = $this->createConfiguredMock(CacheItemInterface::class, [
            'isHit' => false,
        ]);
        $item->method('set')->willReturnSelf();
        $cache_pool = $this->createConfiguredMock(CacheItemPoolInterface::class, [
            'getItem' => $item,
        ]);
        $data_generator = new GroupCountBased();
        $route_parser = new Std();
        $metadata_provider = $this->createStub(MetadataProvider::class);

        $route = new Route("/home");
        $route->setClassName("class");
        $route->setMethodName("method");
        $metadata_provider
            ->method('getRoutes')
            ->willReturn(new RouteCollection([$route]));

        $data_loader = new DataLoader(
            $metadata_provider,
            $cache_pool,
            $data_generator,
            $route_parser
        );

        $this->assertSame([
            [
                'GET' => [
                    "/home" => ["class", "method"]
                ],
            ],
            [],
        ], $data_loader->loadRoutes());
    }
}
