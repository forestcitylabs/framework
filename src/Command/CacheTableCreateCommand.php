<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheTableCreateCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private string $table = 'cache'
    ) {
        parent::__construct('cache:create-table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sm = $this->connection->createSchemaManager();

        // Get the current schema and clone it.
        $fromSchema = $sm->introspectSchema();
        $toSchema = clone $fromSchema;

        // Create session table in the new schema.
        $table = $toSchema->createTable($this->table);
        $table->addColumn('key', 'string')
            ->setLength(128);
        $table->setPrimaryKey(['key']);
        $table->addColumn('data', 'blob');
        $table->addColumn('expires', 'datetime')->setNotNull(false);

        // Create the new table.
        $comparator = $sm->createComparator();
        $schemaDiff = $comparator->compareSchemas($fromSchema, $toSchema);
        $platform = $this->connection->getDatabasePlatform();
        foreach ($platform->getAlterSchemaSQL($schemaDiff) as $sql) {
            $this->connection->executeQuery($sql);
        }

        // Report success.
        $io->success('Created the cache table!');
        return Command::SUCCESS;
    }
}
