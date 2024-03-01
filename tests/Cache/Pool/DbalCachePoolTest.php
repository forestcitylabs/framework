<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Cache\Pool;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use ForestCityLabs\Framework\Cache\CacheItem;
use ForestCityLabs\Framework\Cache\Pool\AbstractCachePool;
use ForestCityLabs\Framework\Cache\Pool\DbalCachePool;
use ForestCityLabs\Framework\Utility\CacheTableManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(DbalCachePool::class)]
#[CoversClass(AbstractCachePool::class)]
#[Group("cache")]
#[UsesClass(CacheItem::class)]
#[UsesClass(CacheTableManager::class)]
class DbalCachePoolTest extends AbstractCachePoolTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        // Create a cache table in memory.
        $this->connection = DriverManager::getConnection(
            (new DsnParser())->parse('pdo-sqlite:///:memory:')
        );
        CacheTableManager::createCacheTable($this->connection, 'cache');

        // Create a cache pool.
        $this->pool = new DbalCachePool($this->connection);

        // Run the normal set-up.
        parent::setUp();
    }
}
