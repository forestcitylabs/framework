<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Routing;

use Cocur\Slugify\Slugify;
use ForestCityLabs\Framework\Routing\Collection\RouteCollection;
use ForestCityLabs\Framework\Routing\MetadataProvider;
use ForestCityLabs\Framework\Tests\Controller\TestController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(MetadataProvider::class)]
#[UsesClass(RouteCollection::class)]
class MetadataProviderTest extends TestCase
{
    #[Test]
    public function testMetadataProvider()
    {
        $item = $this->createConfiguredMock(CacheItemInterface::class, [
            'isHit' => false,
        ]);
        $item->method('set')->willReturnSelf();
        $cache_pool = $this->createConfiguredMock(CacheItemPoolInterface::class, [
            'getItem' => $item,
        ]);
        $metadata_provider = new MetadataProvider(
            [TestController::class],
            $cache_pool,
            $this->createMock(LoggerInterface::class),
            new Slugify(),
        );

        $this->assertEquals(1, $metadata_provider->getRoutes()->count());
        $this->assertEquals('test_test', $metadata_provider->getRoute('test_test')->getName());
        $this->assertEquals(null, $metadata_provider->getRoute('nothing'));
    }
}
