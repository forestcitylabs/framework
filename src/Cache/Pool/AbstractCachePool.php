<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Pool;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractCachePool implements CacheItemPoolInterface
{
    protected array $queue = [];

    protected function checkKey(mixed $key)
    {
        if (!is_string($key) || preg_match('/^[a-zA-Z0-9\-\.]$/', $key) === false) {
            throw new InvalidArgumentException();
        }
    }

    public function getItems(array $keys = []): iterable
    {
        foreach ($keys as $key) {
            $this->checkKey($key);
            yield $key => $this->getItem($key);
        }
    }

    public function deleteItems(array $keys = []): bool
    {
        foreach ($keys as $key) {
            $this->checkKey($key);
            if ($this->deleteItem($key) === false) {
                return false;
            }
        }
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->queue[$item->getKey()] = $item;
        return true;
    }

    public function commit(): bool
    {
        foreach ($this->queue as $item) {
            if ($this->save($item) === false) {
                return false;
            }
        }
        return true;
    }
}
