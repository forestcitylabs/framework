<?php

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\AllowedHostMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(AllowedHostMiddleware::class)]
class AllowedHostMiddlewareTest extends TestCase
{
    #[Test]
    public function validHost()
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);
        $uri->expects($this->once())
            ->method('getHost')
            ->willReturn('example.dev');
        $middleware = new AllowedHostMiddleware(['example.dev'], $factory);

        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request));
        $middleware->process($request, $handler);
    }

    #[Test]
    public function invalidHost()
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);
        $uri->expects($this->once())
            ->method('getHost')
            ->willReturn('example.test');
        $middleware = new AllowedHostMiddleware(['example.dev'], $factory);
        $factory->expects($this->once())
            ->method('createResponse')
            ->with($this->identicalTo(403));
        $middleware->process($request, $handler);
    }
}
