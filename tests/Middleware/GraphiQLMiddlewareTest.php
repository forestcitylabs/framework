<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\GraphiQLMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(GraphiQLMiddleware::class)]
class GraphiQLMiddlewareTest extends TestCase
{
    #[Test]
    public function graphiqlPath(): void
    {
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('withBody')->willReturnSelf();
        $response_factory->method('createResponse')->willReturn($response);
        $stream_factory->expects($this->once())->method('createStream');

        $middleware = new GraphiQLMiddleware($response_factory, $stream_factory);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $uri = $this->createConfiguredMock(UriInterface::class, [
            'getPath' => '/graphiql'
        ]);
        $request->method('getUri')->willReturn($uri);
        $response = $middleware->process($request, $handler);
    }

    #[Test]
    public function notGraphiqlPath(): void
    {
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('withBody')->willReturnSelf();
        $response_factory->method('createResponse')->willReturn($response);
        $stream_factory->expects($this->never())->method('createStream');

        $middleware = new GraphiQLMiddleware($response_factory, $stream_factory);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $uri = $this->createConfiguredMock(UriInterface::class, [
            'getPath' => '/beans'
        ]);
        $request->method('getUri')->willReturn($uri);
        $response = $middleware->process($request, $handler);
    }
}
