<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Pool;

use DateTime;
use ForestCityLabs\Framework\Cache\CacheItem;
use League\Flysystem\Filesystem;
use Psr\Cache\CacheItemInterface;

class FilesystemCachePool extends AbstractCachePool
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->checkKey($key);
        $path = implode(DIRECTORY_SEPARATOR, str_split(md5($key), 4));
        if (!$this->filesystem->has($path)) {
            return new CacheItem($key);
        }
        $item = unserialize($this->filesystem->read($path));
        if (!$item instanceof CacheItem) {
            return new CacheItem($key);
        }
        $now = new DateTime();
        if ($item->getExpires() !== null && $item->getExpires() < $now) {
            return new CacheItem($key);
        }
        return unserialize($this->filesystem->read($path));
    }

    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    public function deleteItem(string $key): bool
    {
        $this->checkKey($key);
        $path = implode(DIRECTORY_SEPARATOR, str_split(md5($key), 4));
        try {
            $this->filesystem->delete($path);
        } catch (\Exception) {
            return false;
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
