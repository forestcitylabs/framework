<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use FastRoute\Dispatcher;
use ForestCityLabs\Framework\Middleware\TrailingSlashMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(TrailingSlashMiddleware::class)]
class TrailingSlashMiddlewareTest extends TestCase
{
    #[Test]
    public function redirectTrailingSlash(): void
    {
        // Mock the services.
        $dispatcher = $this->createStub(Dispatcher::class);
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $redirect_response = $this->createMock(ResponseInterface::class);

        // Configure the services.
        $dispatcher->method('dispatch')->with('GET', '/beans')->willReturn([Dispatcher::FOUND]);
        $response_factory->expects($this->once())
            ->method('createResponse')
            ->with(301)
            ->willReturn($redirect_response);
        $redirect_response->method('withHeader')->willReturnSelf();

        // Create the middleware.
        $middleware = new TrailingSlashMiddleware($dispatcher, $response_factory);

        // Mock the values for the middleware.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $uri = $this->createStub(UriInterface::class);

        // Configure the values.
        $handler->method('handle')->with($request)->willReturn($response);
        $response->method('getStatusCode')->willReturn(404);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/beans/');

        // Run the middleware.
        $middleware->process($request, $handler);
    }

    #[Test]
    public function redirectNonTrailingSlash(): void
    {
        // Mock the services.
        $dispatcher = $this->createStub(Dispatcher::class);
        $response_factory = $this->createMock(ResponseFactoryInterface::class);
        $redirect_response = $this->createMock(ResponseInterface::class);

        // Configure the services.
        $dispatcher->method('dispatch')->with('GET', '/beans/')->willReturn([Dispatcher::FOUND]);
        $response_factory->expects($this->once())
            ->method('createResponse')
            ->with(301)
            ->willReturn($redirect_response);
        $redirect_response->method('withHeader')->willReturnSelf();

        // Create the middleware.
        $middleware = new TrailingSlashMiddleware($dispatcher, $response_factory);

        // Mock the values for the middleware.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $uri = $this->createStub(UriInterface::class);

        // Configure the values.
        $handler->method('handle')->with($request)->willReturn($response);
        $response->method('getStatusCode')->willReturn(404);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/beans');

        // Run the middleware.
        $middleware->process($request, $handler);
    }

    #[Test]
    public function noRedirect(): void
    {
        // Mock the services.
        $dispatcher = $this->createStub(Dispatcher::class);
        $response_factory = $this->createMock(ResponseFactoryInterface::class);

        // Create the middleware.
        $middleware = new TrailingSlashMiddleware($dispatcher, $response_factory);

        // Mock the values for the middleware.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $handler->method('handle')->with($request)->willReturn($response);
        $response->method('getStatusCode')->willReturn(200);

        // Run the middleware.
        $result = $middleware->process($request, $handler);

        // Make assertions.
        $this->assertEquals($response, $result);
    }
}
