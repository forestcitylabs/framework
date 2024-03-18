<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use ForestCityLabs\Framework\Utility\CacheTableManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheTableManager::class)]
class CacheTableManagerTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function createCacheTable(): void
    {
        $connection = DriverManager::getConnection(
            (new DsnParser())->parse('pdo-sqlite:///:memory:')
        );
        CacheTableManager::createCacheTable($connection);
    }
}
