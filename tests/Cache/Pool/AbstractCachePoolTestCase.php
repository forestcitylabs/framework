<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Cache\Pool;

use DateTime;
use ForestCityLabs\Framework\Cache\CacheItem;
use ForestCityLabs\Framework\Cache\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractCachePoolTestCase extends TestCase
{
    protected CacheItemPoolInterface $pool;

    protected function setUp(): void
    {
        // Create some cache items for the tests to check.
        $item = $this->pool->getItem('no_expiry');
        $item->set('no_expiry');
        $this->pool->saveDeferred($item);
        $item = $this->pool->getItem('expired');
        $item->set('expired')->expiresAt(new DateTime('-1 day'));
        $this->pool->saveDeferred($item);
        $item = $this->pool->getItem('future');
        $item->set('future')->expiresAfter(3600);
        $this->pool->saveDeferred($item);
        $item = $this->pool->getItem('delete_me');
        $item->set('delete_me');
        $this->pool->saveDeferred($item);
        $this->pool->commit();
    }

    #[Test]
    public function addItemToCachePool(): void
    {
        $item = $this->pool->getItem('create_new');
        $item->set('create_new');
        $item->expiresAfter(3600);
        $this->pool->save($item);
        $this->assertEquals(false, $item->isHit());

        $item = $this->pool->getItem('create_new');
        $this->assertEquals('create_new', $item->get());
        $this->assertEquals(true, $item->isHit());
        $this->pool->save($item);
    }

    #[Test]
    public function getExpiredItem(): void
    {
        // This is an expired item.
        $item = $this->pool->getItem('expired');
        $this->assertEquals(false, $this->pool->hasItem('expired'));
        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(null, $item->get());
    }

    #[Test]
    public function getNonExistentItem(): void
    {
        $item = $this->pool->getItem('non_existent');
        $this->assertEquals(false, $this->pool->hasItem('non_existent'));
        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(null, $item->get());
    }

    #[Test]
    public function getNoExpiryItem(): void
    {
        $item = $this->pool->getItem('no_expiry');
        $this->assertEquals('no_expiry', $item->get());
        $this->assertEquals(true, $item->isHit());
    }

    #[Test]
    public function getItemWithExpiry(): void
    {
        $item = $this->pool->getItem('future');
        $this->assertEquals(true, $this->pool->hasItem('future'));
        $this->assertEquals('future', $item->get());
    }

    #[Test]
    public function invalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pool->getItem('');
    }

    #[Test]
    public function clearCache(): void
    {
        $this->pool->clear();
        $item = $this->pool->getItem('delete_me');
        $this->assertEquals(false, $item->isHit());
    }

    #[Test]
    public function getItems(): void
    {
        $items = $this->pool->getItems(['future', 'no_expiry', 'non_existent']);
        foreach ($items as $item) {
            $this->assertInstanceOf(CacheItem::class, $item);
            if ($item->getKey() == 'non_existent') {
                $this->assertEquals(false, $item->isHit());
            } else {
                $this->assertEquals(true, $item->isHit());
            }
        }
    }

    #[Test]
    public function deleteItem(): void
    {
        $this->assertEquals(true, $this->pool->hasItem('delete_me'));
        $this->pool->deleteItem('delete_me');
        $item = $this->pool->getItem('delete_me');
        $this->assertEquals(false, $item->isHit());
    }

    #[Test]
    public function deleteItems(): void
    {
        $this->assertTrue($this->pool->hasItem('delete_me'));
        $this->pool->deleteItems(['delete_me']);
        $this->assertFalse($this->pool->hasItem('delete_me'));
    }
}
