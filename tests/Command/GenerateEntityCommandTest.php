<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Command;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory as MappingClassMetadataFactory;
use ForestCityLabs\Framework\Command\GenerateEntityCommand;
use ForestCityLabs\Framework\Utility\CodeGenerator;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(GenerateEntityCommand::class)]
#[UsesClass(Printer::class)]
#[UsesClass(CodeGenerator::class)]
#[Group('development')]
class GenerateEntityCommandTest extends TestCase
{
    use MatchesSnapshots;

    private string $directory;
    private EntityManagerInterface $em;
    private GenerateEntityCommand $command;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Entity' . DIRECTORY_SEPARATOR;
        $factory = $this->createConfiguredStub(MappingClassMetadataFactory::class, [
            'getAllMetadata' => [],
        ]);
        $this->em = $this->createConfiguredStub(EntityManagerInterface::class, [
            'getMetadataFactory' => $factory,
        ]);
        $this->command = new GenerateEntityCommand(
            $this->directory,
            'Application\\Entity',
            new PsrPrinter(),
            $this->em,
            InflectorFactory::create()->build()
        );
        $this->command->setApplication(new Application());
        mkdir($this->directory, recursive: true);
    }

    #[Test]
    public function scalarProperties(): void
    {
        // Set the relevant inputs and execute.
        $tester = new CommandTester($this->command);
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
            '',
            'yes',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User'
        ], ['interactive' => true]);

        // Assert the file now exists.
        $this->assertFileExists($this->directory . 'User.php');

        // Assert that the file has the correct text in it.
        $this->assertMatchesFileSnapshot($this->directory . 'User.php');
    }

    #[Test]
    public function arrayProperty(): void
    {
        $tester = new CommandTester($this->command);
        $tester->setInputs([
            'roles',
            'json_array',
            'y',
            'string',
            '',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User'
        ], ['interactive' => true]);
        $this->assertFileExists($this->directory . 'User.php');
        $this->assertMatchesFileSnapshot($this->directory . 'User.php');
    }

    #[Test]
    public function manyToManyRelationProperty(): void
    {
        // Create the user entity.
        $tester = new CommandTester($this->command);
        $tester->setInputs(['', 'yes']);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'Group',
        ], ['interactive' => true]);

        // Create a group entity.
        $tester = new CommandTester($this->command);
        $tester->setInputs([
            'groups',
            'relation',
            'Group',
            'ManyToMany',
            'y',
            'users',
            '',
            'yes',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User',
        ], ['interactive' => true]);

        $this->assertMatchesFileSnapshot($this->directory . 'User.php');
        $this->assertMatchesFileSnapshot($this->directory . 'Group.php');
    }

    #[Test]
    public function oneToManyRelationProperty(): void
    {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['', 'yes']);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'Group',
        ], ['interactive' => true]);

        // Create a group entity.
        $tester = new CommandTester($this->command);
        $tester->setInputs([
            'groups',
            'relation',
            'Group',
            'OneToMany',
            'y',
            'users',
            '',
            'yes',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User',
        ], ['interactive' => true]);

        $this->assertMatchesFileSnapshot($this->directory . 'User.php');
        $this->assertMatchesFileSnapshot($this->directory . 'Group.php');
    }

    #[Test]
    public function manyToOneRelationProperty(): void
    {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['', 'yes']);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'Group',
        ], ['interactive' => true]);

        // Create a group entity.
        $tester = new CommandTester($this->command);
        $tester->setInputs([
            'groups',
            'relation',
            'Group',
            'ManyToOne',
            'y',
            'users',
            '',
            'yes',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User',
        ], ['interactive' => true]);

        $this->assertMatchesFileSnapshot($this->directory . 'User.php');
        $this->assertMatchesFileSnapshot($this->directory . 'Group.php');
    }

    #[Test]
    public function oneToOneRelationProperty(): void
    {
        $tester = new CommandTester($this->command);
        $tester->setInputs(['', 'yes']);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'Group',
        ], ['interactive' => true]);

        // Create a group entity.
        $tester = new CommandTester($this->command);
        $tester->setInputs([
            'groups',
            'relation',
            'Group',
            'OneToOne',
            'y',
            'users',
            '',
            'yes',
        ]);
        $tester->execute([
            'command' => 'generate:entity',
            'class' => 'User',
        ], ['interactive' => true]);

        $this->assertMatchesFileSnapshot($this->directory . 'User.php');
        $this->assertMatchesFileSnapshot($this->directory . 'Group.php');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->directory . 'User.php')) {
            unlink($this->directory . 'User.php');
        }
        if (file_exists($this->directory . 'Group.php')) {
            unlink($this->directory . 'Group.php');
        }
        rmdir($this->directory);
    }
}
