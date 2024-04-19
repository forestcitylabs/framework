<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeHelper;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GraphQLSchemaDiffCommand extends Command
{
    public function __construct(
        private string $schema_file,
        private Schema $schema,
    ) {
        parent::__construct('graphql:schema-diff');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // The desired schema is loaded from a graphql file.
        $new = BuildSchema::build(
            Parser::parse(file_get_contents($this->schema_file))
        );

        // The current schema is found in code.
        $old = $this->schema;

        $differ = new Differ(new UnifiedDiffOutputBuilder());
        $opts = ['sortArguments' => true, 'sortEnumValues' => true, 'sortFields' => true, 'sortInputFields' => true, 'sortTypes' => true];
        $diff = $differ->diff(
            SchemaPrinter::doPrint($old, $opts),
            SchemaPrinter::doPrint($new, $opts),
        );

        foreach (preg_split("/\r\n|\n|\r/", $diff) as $line) {
            if (substr($line, 0, 1) === '-') {
                $output->writeln('<fg=red>' . $line . '</>');
            } elseif (substr($line, 0, 1) === '+') {
                $output->writeln('<fg=green>' . $line . '</>');
            } else {
                $output->writeln($line);
            }
        }

        return Command::SUCCESS;
    }
}
