<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Session\Driver;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use ForestCityLabs\Framework\Session\Driver\DbalSessionDriver;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Utility\SessionTableManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Ramsey\Uuid\Doctrine\UuidBinaryType;

#[CoversClass(DbalSessionDriver::class)]
#[UsesClass(Session::class)]
#[UsesClass(SessionTableManager::class)]
#[Group('session')]
class DbalSessionDriverTest extends AbstractSessionDriverTestCase
{
    protected function setUp(): void
    {
        // Create a cache table in memory.
        $connection = DriverManager::getConnection(
            (new DsnParser())->parse('pdo-sqlite:///:memory:')
        );
        try {
            Type::addType('uuid_binary', UuidBinaryType::class);
        } catch (Exception) {
        }

        $connection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid_binary', 'binary');
        SessionTableManager::createSessionTable($connection, 'sessions');

        // Create a cache pool.
        $this->driver = new DbalSessionDriver($connection);
    }
}
