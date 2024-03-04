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
        $command = new GenerateEntityCommand(
            sys_get_temp_dir(),
            'Application\\Entity',
            $this->createMock(Printer::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Inflector::class)
        );
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->setInputs([
            'email',
            'string',
            'n',
            'y',
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
