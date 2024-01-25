<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GraphQLDumpSchemaCommand extends Command
{
    public function __construct(private Schema $schema)
    {
        parent::__construct('graphql:dump-schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->text((new SchemaPrinter())->doPrint($this->schema));
        return Command::SUCCESS;
    }
}
