<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Cache;

use DateInterval;
use DateTime;
use ForestCityLabs\Framework\Cache\CacheItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheItem::class)]
class CacheItemTest extends TestCase
{
    #[Test]
    public function minimalCacheItem(): void
    {
        $item = new CacheItem("test", "value");
        $this->assertSame("test", $item->getKey());
        $this->assertSame("value", $item->get());
        $this->assertSame(null, $item->getExpires());
        $this->assertSame(false, $item->isHit());

        $item->set("value2");
        $this->assertEquals("value2", $item->get());

        $date = new DateTime("+1 day");
        $item->expiresAt($date);
        $this->assertSame($date, $item->getExpires());

        $item->expiresAt(null);
        $this->assertSame(null, $item->getExpires());
    }

    #[Test]
    public function expiresAfterInterval(): void
    {
        $item = new CacheItem("test");
        $now = new DateTime();
        $after = new DateInterval("P2D");
        $item->expiresAfter($after);
        $this->assertEquals($now->add($after)->format("Y-m-d"), $item->getExpires()->format("Y-m-d"));

        $item->expiresAfter(null);
        $this->assertEquals(null, $item->getExpires());
    }

    #[Test]
    public function expiresAfterInteger(): void
    {
        $item = new CacheItem("test");
        $now = new DateTime();
        $after = 3600;
        $item->expiresAfter($after);
        $this->assertEquals($now->add(DateInterval::createFromDateString("3600 seconds"))->format("H"), $item->getExpires()->format("H"));
    }

    #[Test]
    public function testSerialization(): void
    {
        $item = new CacheItem("test", "beans");
        $string = serialize($item);
        $item = unserialize($string);
        $this->assertEquals(true, $item->isHit());
    }
}
