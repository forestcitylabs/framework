<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Driver;

use ForestCityLabs\Framework\Cache\CacheItem;
use Psr\Cache\InvalidArgumentException;
use League\Flysystem\Filesystem;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class FilesystemCacheDriver implements CacheItemPoolInterface
{
    private array $queue = [];

    public function __construct(private Filesystem $filesystem)
    {
    }

    public function getItem(string $key): CacheItemInterface
    {
        if (preg_match('/^[a-zA-Z0-9\-\.]$/', $key) === false) {
            throw new InvalidArgumentException();
        }
        $path = implode(DIRECTORY_SEPARATOR, str_split(md5($key), 4));
        if (!$this->filesystem->has($path)) {
            return new CacheItem($key);
        }
        return unserialize($this->filesystem->read($path));
    }

    public function getItems(array $keys = []): iterable
    {
        foreach ($keys as $key) {
            if (!is_string($key) || preg_match('/^[a-zA-Z0-9\-\.]$/', $key) === false) {
                throw new InvalidArgumentException();
            }
            yield $key => $this->getItem($key);
        }
    }

    public function hasItem(string $key): bool
    {
        if (preg_match('/^[a-zA-Z0-9\-\.]$/', $key) === false) {
            throw new InvalidArgumentException();
        }
        $path = implode(DIRECTORY_SEPARATOR, str_split(md5($key), 4));
        return $this->filesystem->has($path);
    }

    public function deleteItem(string $key): bool
    {
        if (preg_match('/^[a-zA-Z0-9\-\.]$/', $key) === false) {
            throw new InvalidArgumentException();
        }
        $path = implode(DIRECTORY_SEPARATOR, str_split(md5($key), 4));
        try {
            $this->filesystem->delete($path);
        } catch (\Exception) {
            return false;
        }
        return true;
    }

    public function deleteItems(array $keys = []): bool
    {
        foreach ($keys as $key) {
            if (!is_string($key) || preg_match('/^[a-zA-Z0-9\-\.]$/', $key) === false) {
                throw new InvalidArgumentException();
            }
            if ($this->deleteItem($key) === false) {
                return false;
            }
        }
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $path = implode(DIRECTORY_SEPARATOR, str_split(md5($item->getKey()), 4));
        if ($this->filesystem->has($path)) {
            $this->filesystem->delete($path);
        }
        try {
            $this->filesystem->write($path, serialize($item));
        } catch (\Exception) {
            return false;
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

    public function clear(): bool
    {
        try {
            $this->filesystem->deleteDirectory('/');
        } catch (\Exception) {
            return false;
        }
        return true;
    }
}
