<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Cache\Pool;

use DateTime;
use ForestCityLabs\Framework\Cache\CacheItem;
use ForestCityLabs\Framework\Cache\Pool\AbstractCachePool;
use ForestCityLabs\Framework\Cache\Pool\PredisCachePool;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Predis\Client;

#[CoversClass(PredisCachePool::class)]
#[CoversClass(AbstractCachePool::class)]
#[UsesClass(CacheItem::class)]
#[Group("cache")]
class PredisCachePoolTest extends AbstractCachePoolTestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        $this->client = Mockery::spy(Client::class);
        $this->pool = new PredisCachePool($this->client);

        // The create_new key will initially be null and then return a value.
        $this->client->allows()->get('create_new')->andReturn(null, serialize(new CacheItem('create_new', 'create_new')));

        // The no_expiry item will always return an item.
        $this->client->allows()->get('no_expiry')->andReturn(serialize(new CacheItem('no_expiry', 'no_expiry')));

        // The expired item will not return an item.
        $this->client->allows()->get('expired')->andReturn(null);
        $this->client->allows()->exists('expired')->andReturn(0);

        // The future item will return an item that expires in the future then nothing.
        $this->client->allows()->get('future')->andReturn(serialize(new CacheItem('future', 'future', new DateTime('+1 day'))));
        $this->client->allows()->exists('future')->andReturn(1);

        // The delete me item will be present initially and then not present.
        $this->client->allows()->get('delete_me')->andReturn(null);
        $this->client->allows()->exists('delete_me')->andReturn(1, 0);

        // Non existent will never return.
        $this->client->allows()->get('non_existent')->andReturn(null);
    }

    #[Test]
    public function clearCache(): void
    {
        $this->client->allows()->get('no_expiry')->andReturn(null);
        parent::clearCache();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
