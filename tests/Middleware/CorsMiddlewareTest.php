<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\CorsMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(CorsMiddleware::class)]
class CorsMiddlewareTest extends TestCase
{
    #[Test]
    public function validWildcardCorsRequest()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('origin')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('origin')
            ->willReturn(['https://example.dev']);
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('access-control-allow-origin', '*')
            ->willReturnSelf();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);
        $factory = $this->createStub(ResponseFactoryInterface::class);

        $middleware = new CorsMiddleware($factory, ['*']);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function validSpecificCorsRequest()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('origin')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('origin')
            ->willReturn(['https://example.dev']);
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('withHeader')
            ->with('access-control-allow-origin', 'https://example.dev')
            ->willReturnSelf();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);
        $factory = $this->createStub(ResponseFactoryInterface::class);

        $middleware = new CorsMiddleware($factory, ['https://example.dev']);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function invalidCorsRequest()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('origin')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('origin')
            ->willReturn(['https://example2.dev']);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createResponse')
            ->with(403);

        $middleware = new CorsMiddleware($factory, ['https://example.dev']);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function optionsRequest()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('origin')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('origin')
            ->willReturn(['https://example.dev']);
        $request->method('getMethod')
            ->willReturn('OPTIONS');

        $response = $this->createMock(ResponseInterface::class);

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createResponse')
            ->with(204)
            ->willReturn($response);
        $matcher = $this->exactly(4);
        $response->expects($matcher)
            ->method('withHeader')
            ->willReturnCallback(function (string $header, $value) use ($matcher, $response) {
                list($check_header, $check_value) = match ($matcher->numberOfInvocations()) {
                    1 => ['access-control-allow-origin', 'https://example.dev'],
                    2 => ['access-control-allow-headers', 'Authorization'],
                    3 => ['access-control-allow-methods', 'GET, POST'],
                    4 => ['access-control-max-age', 3600],
                };
                $this->assertEquals($header, $check_header);
                $this->assertEquals($value, $check_value);
                return $response;
            });

        $handler = $this->createStub(RequestHandlerInterface::class);
        $middleware = new CorsMiddleware($factory, ['https://example.dev'], ['Authorization'], ['GET', 'POST'], 3600);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function invalidMethod()
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('origin')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('origin')
            ->willReturn(['https://example.dev']);
        $request->method('getMethod')
            ->willReturn('POST');
        $response = $this->createStub(ResponseInterface::class);
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->once())
            ->method('createResponse')
            ->with(403)
            ->willReturn($response);
        $middleware = new CorsMiddleware($factory, ['https://example.dev'], allow_methods: ['GET']);
        $middleware->process($request, $this->createMock(RequestHandlerInterface::class));
    }

    #[Test]
    public function noOrigin()
    {
        $request = $this->createConfiguredStub(ServerRequestInterface::class, [
            'hasHeader' => false,
        ]);
        $handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $this->createStub(ResponseInterface::class),
        ]);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);
        $middleware = new CorsMiddleware($this->createStub(ResponseFactoryInterface::class));
        $middleware->process($request, $handler);
    }
}
