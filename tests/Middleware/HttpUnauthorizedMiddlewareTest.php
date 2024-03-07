<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\HttpUnauthorizedMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

#[CoversClass(HttpUnauthorizedMiddleware::class)]
class HttpUnauthorizedMiddlewareTest extends TestCase
{
    #[Test]
    public function render401(): void
    {
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $twig = $this->createMock(Environment::class);

        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 401,
        ]);
        $response->expects($this->once())->method('withHeader')->with('Location', '/login')->willReturnSelf();
        $response->expects($this->once())->method('withStatus')->with(302)->willReturnSelf();

        $middleware = new HttpUnauthorizedMiddleware($response_factory, $stream_factory, $twig);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware->process($request, $handler);
    }
}
