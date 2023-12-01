<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Pool;

use Doctrine\DBAL\Connection;
use ForestCityLabs\Framework\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;

class DbalCachePool extends AbstractCachePool
{
    public function __construct(
        private Connection $connection,
        private string $table = 'cache'
    ) {
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->checkKey($key);
        $qb = $this->connection->createQueryBuilder();
        $qb->select('data')
            ->from($this->table)
            ->where('key = :key')
            ->setParameter(':key', $key);
        if (false === $result = $qb->executeQuery()->fetchOne()) {
            return new CacheItem($key);
        }
        return unserialize($result);
    }

    public function hasItem(string $key): bool
    {
        $this->checkKey($key);
        $qb = $this->connection->createQueryBuilder();
        $qb->select('key')
            ->from($this->table)
            ->where('key = :key')
            ->setParameter(':key', $key);
        if (false === $qb->executeQuery()->fetchOne()) {
            return false;
        }
        return true;
    }

    public function deleteItem(string $key): bool
    {
        $this->checkKey($key);
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->where('key = :key')
            ->setParameter(':key', $key)
            ->executeQuery();

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('key')
            ->from($this->table)
            ->where('key = :key')
            ->setParameter(':key', $item->getKey());
        $this->connection->beginTransaction();
        if (false !== $qb->executeQuery()->fetchOne()) {
            $qb = $this->connection->createQueryBuilder();
            $qb->delete($this->table)
                ->where('key = :key')
                ->setParameter(':key', $item->getKey())
                ->executeQuery();
        }
        $qb = $this->connection->createQueryBuilder();
        $qb->insert($this->table)
            ->values(['key' => $item->getKey(), 'data' => serialize($item)])
            ->executeQuery();
        $this->connection->commit();
        return true;
    }

    public function clear(): bool
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->executeQuery();
        return true;
    }
}
