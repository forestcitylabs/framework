<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Pool;

use DateTime;
use ForestCityLabs\Framework\Cache\CacheItem;
use Predis\ClientInterface;
use Psr\Cache\CacheItemInterface;

class PredisCachePool extends AbstractCachePool
{
    public function __construct(
        private ClientInterface $client
    ) {
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->checkKey($key);
        if (null === $value = $this->client->get($key)) {
            return new CacheItem($key);
        }
        $item = new CacheItem($key, unserialize($value));
        if (0 < $expires = $this->client->expiretime($key)) {
            $item->expiresAt(new DateTime('@' . $expires));
        }
        return $item;
    }

    public function hasItem(string $key): bool
    {
        $this->checkKey($key);
        return (1 === $this->client->exists($key));
    }

    public function deleteItem(string $key): bool
    {
        $this->checkKey($key);
        $this->client->del($key);
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        // We can only persist our cache items.
        if (!$item instanceof CacheItem) {
            return false;
        }

        // Set the cache item in redis.
        $this->client->set($item->getKey(), serialize($item->get()));

        // If we have an expiry set it on the cache item.
        if (null !== $item->getExpires()) {
            $this->client->expireat($item->getKey(), $item->getExpires()->format('U'));
        }

        // Indicate success.
        return true;
    }

    public function clear(): bool
    {
        $this->client->del($this->client->keys('*'));
        return true;
    }
}
