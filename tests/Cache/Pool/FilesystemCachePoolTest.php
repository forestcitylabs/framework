<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Cache\Pool;

use ForestCityLabs\Framework\Cache\CacheItem;
use ForestCityLabs\Framework\Cache\Pool\AbstractCachePool;
use ForestCityLabs\Framework\Cache\Pool\FilesystemCachePool;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(FilesystemCachePool::class)]
#[CoversClass(AbstractCachePool::class)]
#[Group("cache")]
#[UsesClass(CacheItem::class)]
class FilesystemCachePoolTest extends AbstractCachePoolTestCase
{
    protected function setUp(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->pool = new FilesystemCachePool($filesystem);
        parent::setUp();
    }
}
