<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Pool;

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
        $item = unserialize($value);
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
        $this->client->set($item->getKey(), serialize($item));

        // If we have an expiry set it on the cache item.
        if (null !== $item->getExpires()) {
            $this->client->expireat($item->getKey(), $item->getExpires()->format('U'));
        }

        // Indicate success.
        return true;
    }

    public function clear(): bool
    {
        // Get the prefix.
        $prefix = $this->client->getOptions()->prefix?->getPrefix() ?? '';

        // Store a cursor to delete all keys with the prefix.
        $cursor = 0;

        // Scan for keys and delete them.
        do {
            [$cursor, $keys] = $this->client->scan($cursor, ['MATCH' => $prefix . '*', 'COUNT' => 1000]);
            foreach ($keys as $key) {
                $this->client->del(substr($key, strlen($prefix)));
            }
        } while ((string) $cursor !== '0');

        // Return success.
        return true;
    }
}
