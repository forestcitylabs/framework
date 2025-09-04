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
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function getItemHandlesCorruptedCache(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $pool = new FilesystemCachePool($filesystem);

        $filesystem->method('has')->willReturn(true);
        // Return data that will fail unserialize() and trigger the error path
        $filesystem->method('read')->willReturn('corrupted_data_not_serialized');

        // Suppress the warning from unserialize for this test
        $item = @$pool->getItem('test');
        $this->assertFalse($item->isHit());
        $this->assertEquals('test', $item->getKey());
    }

    #[Test]
    public function saveHandlesFileSystemException(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $pool = new FilesystemCachePool($filesystem);

        $item = new CacheItem('test');
        $item->set('value');

        $filesystem->method('has')->willReturn(false);
        $filesystem->method('write')->willThrowException(new \Exception('Write failed'));

        $result = $pool->save($item);
        $this->assertFalse($result);
    }

    #[Test]
    public function deleteItemHandlesFileSystemException(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $pool = new FilesystemCachePool($filesystem);

        $filesystem->method('delete')->willThrowException(new \Exception('Delete failed'));

        $result = $pool->deleteItem('test');
        $this->assertFalse($result);
    }

    #[Test]
    public function clearHandlesFileSystemException(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $pool = new FilesystemCachePool($filesystem);

        $filesystem->method('deleteDirectory')->willThrowException(new \Exception('Clear failed'));

        $result = $pool->clear();
        $this->assertFalse($result);
    }
}
