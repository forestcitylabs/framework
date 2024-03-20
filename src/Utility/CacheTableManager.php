<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility;

use Doctrine\DBAL\Connection;

class CacheTableManager
{
    public static function createCacheTable(
        Connection $connection,
        string $table = 'cache'
    ): void {
        $sm = $connection->createSchemaManager();

        // Get the current schema and clone it.
        $fromSchema = $sm->introspectSchema();
        $toSchema = clone $fromSchema;

        // Create session table in the new schema.
        $table = $toSchema->createTable($table);
        $table->addColumn('key', 'string')
            ->setLength(128);
        $table->setPrimaryKey(['key']);
        $table->addColumn('data', 'blob');
        $table->addColumn('expires', 'datetime')->setNotNull(false);

        // Create the new table.
        $comparator = $sm->createComparator();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);
        $platform = $connection->getDatabasePlatform();
        foreach ($platform->getAlterSchemaSQL($schemaDiff) as $sql) {
            $connection->executeQuery($sql);
        }
    }
}
