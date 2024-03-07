<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility;

use Doctrine\DBAL\Connection;

class SessionTableManager
{
    public static function createSessionTable(
        Connection $connection,
        string $table
    ): void {
        $sm = $connection->createSchemaManager();

        // Get the current schema and clone it.
        $fromSchema = $sm->introspectSchema();
        $toSchema = clone $fromSchema;

        // Create session table in the new schema.
        $table = $toSchema->createTable($table);
        $table->addColumn('id', 'uuid_binary');
        $table->setPrimaryKey(['id']);
        $table->addColumn('data', 'blob');

        // Create the new table.
        $comparator = $sm->createComparator();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);
        $platform = $connection->getDatabasePlatform();
        foreach ($platform->getAlterSchemaSQL($schemaDiff) as $sql) {
            $connection->executeQuery($sql);
        }
    }
}
