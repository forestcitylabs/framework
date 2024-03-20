<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Command;

use ForestCityLabs\Framework\Command\GraphQLDumpSchemaCommand;
use GraphQL\Type\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GraphQLDumpSchemaCommand::class)]
class GraphQLDumpSchemaCommandTest extends TestCase
{
    use MatchesSnapshots;

    #[Test]
    public function process(): void
    {
        $command = new GraphQLDumpSchemaCommand(new Schema([]));
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->execute(['command' => 'graphql:dump-schema']);
        $this->assertMatchesSnapshot($tester->getDisplay());
    }
}
