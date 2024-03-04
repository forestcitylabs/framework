<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Command;

use Doctrine\Inflector\Inflector;
use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Command\GenerateEntityCommand;
use Nette\PhpGenerator\Printer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GenerateEntityCommand::class)]
#[Group('development')]
class GenerateEntityCommandTest extends TestCase
{
    #[Test]
    public function generateEntity(): void
    {
        $inflector = $this->createMock(Inflector::class);
        $inflector->method('singularize')->willReturnArgument(0);
        $command = new GenerateEntityCommand(
            sys_get_temp_dir(),
            'Application\\Entity',
            $this->createMock(Printer::class),
            $this->createMock(EntityManagerInterface::class),
            $inflector
        );
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs([
            'email',
            'string',
            'n',
            'y',
            'birthday',
            'datetime',
            'y',
            'n',
            'age',
            'integer',
            'y',
            'n',
            'consent',
            'boolean',
            'n',
            'y',
            'bio',
            'text',
            'y',
            'n',
            'exchange',
            'float',
            'y',
            'n',
            'roles',
            'json_array',
            'y',
            'string',
            '',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User'
        ], [
            'interactive' => true,
        ]);
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'User.php');
    }

    protected function tearDown(): void
    {
        unlink(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'User.php');
    }
}
