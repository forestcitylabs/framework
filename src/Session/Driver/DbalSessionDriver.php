<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Session\Driver;

use Doctrine\DBAL\Connection;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
use iterable;
use Ramsey\Uuid\UuidInterface;

class DbalSessionDriver implements SessionDriverInterface
{
    public function __construct(
        private Connection $connection,
        private string $table = 'sessions'
    ) {
    }

    public function load(UuidInterface $uuid): ?Session
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('data')
            ->from($this->table)
            ->where('id = :id')
            ->setParameter(':id', $uuid);
        if (false === $result = $qb->executeQuery()->fetchOne()) {
            return null;
        }

        return unserialize($result);
    }

    public function loadAll(): iterable
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('data')
            ->from($this->table);
        foreach ($qb->executeQuery()->fetchAllAssociative() as $result) {
            yield unserialize($result['data']);
        }
    }

    public function save(Session $session): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('id')
            ->from($this->table)
            ->where('id = :id')
            ->setParameter(':id', $session->getId());
        $this->connection->beginTransaction();
        if (false !== $qb->executeQuery()->fetchOne()) {
            $qb = $this->connection->createQueryBuilder();
            $qb->delete($this->table)
                ->where('id = :id')
                ->setParameter(':id', $session->getId())
                ->executeQuery();
        }
        $qb = $this->connection->createQueryBuilder();
        $qb->insert($this->table)
            ->values(['id' => $session->getId(), 'data' => serialize($session)])
            ->executeQuery();
        $this->connection->commit();
    }

    public function delete(Session $session): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->where('id = :id')
            ->setParameter(':id', $session->getId())
            ->executeQuery();
    }

    public function deleteAll(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->executeQuery();
    }
}
