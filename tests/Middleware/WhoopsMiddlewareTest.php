<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use Exception;
use ForestCityLabs\Framework\Middleware\WhoopsMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Whoops\Run;

#[CoversClass(WhoopsMiddleware::class)]
#[UsesClass(Run::class)]
class WhoopsMiddlewareTest extends TestCase
{
    #[Test]
    public function handleError(): void
    {
        // Mock services.
        $whoops = new Run();
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        // Configure services.
        $response->method('withBody')->willReturnSelf();
        $response_factory->expects($this->once())->method('createResponse')->with(500)->willReturn($response);

        // Create middleware.
        $middleware = new WhoopsMiddleware($whoops, $response_factory, $stream_factory);

        // Create values.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);

        // Configure values.
        $handler->method('handle')->with($request)->willThrowException(new Exception());

        // Run the middleware.
        $response = $middleware->process($request, $handler);
    }
}
