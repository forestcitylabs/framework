<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Pool;

use DateTime;
use Doctrine\DBAL\ArrayParameterType;
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
        // Ensure we are using a valid key.
        $this->checkKey($key);

        // Look for a non-expired cache item with this key.
        $qb = $this->connection->createQueryBuilder();
        $qb->select('data', 'expires')
            ->from($this->table)
            ->where('key = :key')
            ->setParameter('key', $key)
            ->andWhere($qb->expr()->or(
                $qb->expr()->isNull('expires'),
                $qb->expr()->gt('expires', ':now')
            ))
            ->setParameter('now', new DateTime(), 'datetime');

        // If we cannot get a result return a new cache item.
        if (false === $result = $qb->executeQuery()->fetchAssociative()) {
            return new CacheItem($key);
        }

        // Cache item is good, return.
        return new CacheItem($key, unserialize($result['data']), $result['expires'] ? new DateTime($result['expires']) : null, true);
    }

    public function getItems(array $keys = []): iterable
    {
        foreach ($keys as $key) {
            $this->checkKey($key);
        }

        // Get all cache items from the passed keys.
        $qb = $this->connection->createQueryBuilder();
        $qb->select('key', 'data', 'expires')
            ->from($this->table)
            ->where('key IN (:keys)')
            ->setParameter('keys', $keys, ArrayParameterType::STRING)
            ->andWhere($qb->expr()->or(
                $qb->expr()->isNull('expires'),
                $qb->expr()->gt('expires', ':now')
            ))
            ->setParameter('now', new DateTime(), 'datetime');

        // Pass back the results.
        $result = $qb->executeQuery()->fetchAllAssociativeIndexed();
        foreach ($keys as $key) {
            if (isset($result[$key])) {
                $item = new CacheItem($key, unserialize($result[$key]['data']), $result[$key]['expires'] ? new DateTime($result[$key]['expires']) : null, true);
            } else {
                $item = new CacheItem($key);
            }
            yield $key => $item;
        }
    }

    public function hasItem(string $key): bool
    {
        // Ensure this is a valid key.
        $this->checkKey($key);

        // Check if this key exists in the database.
        $qb = $this->connection->createQueryBuilder();
        $qb->select('key')
            ->from($this->table)
            ->where('key = :key')
            ->setParameter('key', $key)
            ->andWhere($qb->expr()->or(
                $qb->expr()->isNull('expires'),
                $qb->expr()->gt('expires', ':now')
            ))
            ->setParameter('now', new DateTime(), 'datetime');

        // If this is not in the database return false.
        if (false === $qb->executeQuery()->fetchOne()) {
            return false;
        }

        // Return true.
        return true;
    }

    public function deleteItem(string $key): bool
    {
        // Check if the key is valid.
        $this->checkKey($key);

        // Delete the row from the database.
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->where('key = :key')
            ->setParameter('key', $key)
            ->executeQuery();

        // Always return true.
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        // Ensure we are working with our own cache item.
        if (!$item instanceof CacheItem) {
            return false;
        }

        // Check if this key exists in the database.
        $qb = $this->connection->createQueryBuilder();
        $qb->select('key')
            ->from($this->table)
            ->where('key = :key')
            ->setParameter('key', $item->getKey());

        // Begin a transaction to delete and save the new item.
        $this->connection->beginTransaction();
        if (false !== $qb->executeQuery()->fetchOne()) {
            $qb = $this->connection->createQueryBuilder();
            $qb->delete($this->table)
                ->where('key = :key')
                ->setParameter('key', $item->getKey())
                ->executeQuery();
        }

        // Save the new item in the database.
        $qb = $this->connection->createQueryBuilder();
            $qb->insert($this->table)
                ->values([
                    'key' => ':key',
                    'data' => ':data',
                    'expires' => ':expires',
                ])->setParameters([
                    'key' => $item->getKey(),
                    'data' => serialize($item->get()),
                    'expires' => $item->getExpires(),
                ], ['expires' => 'datetime'])
                ->executeQuery();

        // Commit the transaction and return true.
        $this->connection->commit();
        return true;
    }

    public function clear(): bool
    {
        // Delete the entire contents of the table.
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->executeQuery();
        return true;
    }
}
