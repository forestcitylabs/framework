<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Routing;

use Cocur\Slugify\Slugify;
use ForestCityLabs\Framework\Routing\Attribute\Route;
use ForestCityLabs\Framework\Routing\Attribute\RoutePrefix;
use ForestCityLabs\Framework\Routing\Collection\RouteCollection;
use ForestCityLabs\Framework\Routing\MetadataProvider;
use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;

#[CoversClass(MetadataProvider::class)]
#[UsesClass(RouteCollection::class)]
#[UsesClass(Route::class)]
#[UsesClass(RoutePrefix::class)]
class MetadataProviderTest extends TestCase
{
    use MatchesSnapshots;

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
            [UserController::class],
            $cache_pool,
            $this->createMock(LoggerInterface::class),
            new Slugify(),
        );

        $this->assertMatchesObjectSnapshot($metadata_provider->getRoutes());
    }
}
