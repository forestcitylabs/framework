<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Session\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
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
            ->setParameter('id', $uuid->getBytes(), ParameterType::BINARY);
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
            ->setParameter('id', $session->getId()->getBytes(), ParameterType::BINARY);
        $this->connection->beginTransaction();
        if (false !== $qb->executeQuery()->fetchOne()) {
            $qb = $this->connection->createQueryBuilder();
            $qb->delete($this->table)
                ->where('id = :id')
                ->setParameter('id', $session->getId()->getBytes(), ParameterType::BINARY)
                ->executeQuery();
        }
        $qb = $this->connection->createQueryBuilder();
        $qb->insert($this->table)
            ->values([
                'id' => ':id',
                'data' => ':data',
            ])
            ->setParameter('id', $session->getId()->getBytes(), ParameterType::BINARY)
            ->setParameter('data', serialize($session))
            ->executeQuery();
        $this->connection->commit();
    }

    public function delete(Session $session): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->where('id = :id')
            ->setParameter('id', $session->getId()->getBytes(), ParameterType::BINARY)
            ->executeQuery();
    }

    public function deleteAll(): void
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->delete($this->table)
            ->executeQuery();
    }
}
