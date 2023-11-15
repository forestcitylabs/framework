<?php

namespace ForestCityLabs\Framework\Tests;

use ForestCityLabs\Framework\Kernel;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Kernel::class)]
class KernelTest extends TestCase
{
    #[Test]
    public function addMiddleware()
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with($this->identicalTo('Adding middleware "TestMiddleware".'));
        $kernel = new Kernel(
            $container,
            $dispatcher,
            $logger
        );

        $kernel->addMiddleware('TestMiddleware');
    }
}
