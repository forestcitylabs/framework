<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Command;

use ForestCityLabs\Framework\Command\CacheClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CacheClearCommand::class)]
class CacheClearCommandTest extends TestCase
{
    #[Test]
    public function execute(): void
    {
        $pool_one = $this->createMock(CacheItemPoolInterface::class);
        $pool_one->expects($this->once())
            ->method('clear');
        $pool_two = $this->createMock(CacheItemPoolInterface::class);
        $pool_two->expects($this->once())
            ->method('clear');
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'beans';
        touch($file);
        $command = new CacheClearCommand([
            $pool_one,
            $pool_two,
        ], [
            $file,
        ]);
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->execute(['command' => 'cache:clear']);
        $this->assertStringContainsString('[OK]', $tester->getDisplay());
        $this->assertFileDoesNotExist($file);
    }
}
