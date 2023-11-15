<?php

namespace ForestCityLabs\Framework\Tests;

use ForestCityLabs\Framework\Events\PostMiddlewareHandleEvent;
use ForestCityLabs\Framework\Events\PreMiddlewareHandleEvent;
use ForestCityLabs\Framework\Kernel;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

#[CoversClass(Kernel::class)]
#[UsesClass(PreMiddlewareHandleEvent::class)]
#[UsesClass(PostMiddlewareHandleEvent::class)]
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

    #[Test]
    public function handle()
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);

        // Expect the get method to be called on the container.
        $container->expects($this->once())
            ->method('get')
            ->with($this->identicalTo('TestMiddleware'))
            ->willReturn($middleware);

        // Expect the dispatcher to be called twice.
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        // Create the kernel, add the middleware and handle a request.
        $kernel = new Kernel($container, $dispatcher, $logger);
        $kernel->addMiddleware('TestMiddleware');
        $response = $kernel->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
