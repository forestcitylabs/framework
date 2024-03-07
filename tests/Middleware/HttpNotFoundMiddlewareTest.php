<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\HttpNotFoundMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

#[CoversClass(HttpNotFoundMiddleware::class)]
class HttpNotFoundMiddlewareTest extends TestCase
{
    #[Test]
    public function render404(): void
    {
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $stream_factory = $this->createMock(StreamFactoryInterface::class);
        $twig = $this->createMock(Environment::class);

        $response = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 404,
        ]);
        $response->method('withBody')->willReturnSelf();
        $twig->expects($this->once())->method('render')->with('errors/404.html.twig');

        $middleware = new HttpNotFoundMiddleware($response_factory, $stream_factory, $twig);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $middleware->process($request, $handler);
    }
}
