<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use FastRoute\Dispatcher;
use ForestCityLabs\Framework\Events\PreRouteDispatchEvent;
use ForestCityLabs\Framework\Middleware\RoutingMiddleware;
use ForestCityLabs\Framework\Routing\MetadataProvider;
use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(RoutingMiddleware::class)]
#[UsesClass(PreRouteDispatchEvent::class)]
class RoutingMiddlewareTest extends TestCase
{
    #[Test]
    public function routeNotFound(): void
    {
        // Create mock services.
        $container = $this->createStub(ContainerInterface::class);
        $dispatcher = $this->createStub(Dispatcher::class);
        $event_dispatcher = $this->createStub(EventDispatcherInterface::class);
        $metadata_provider = $this->createStub(MetadataProvider::class);
        $response_factory = $this->createStub(ResponseFactoryInterface::class);
        $parameter_processor = $this->createStub(ParameterProcessor::class);

        // Configure the services.
        $dispatcher->method('dispatch')->willReturn([Dispatcher::NOT_FOUND]);
        $response_factory->method('createResponse')->with(404)->willReturn(
            $this->createStub(ResponseInterface::class)
        );

        // Create the middleware.
        $middleware = new RoutingMiddleware(
            $container,
            $dispatcher,
            $event_dispatcher,
            $metadata_provider,
            $response_factory,
            $parameter_processor
        );

        // Create the values for processing.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $uri = $this->createStub(UriInterface::class);

        // Configure the values.
        $request->method('getUri')->willReturn($uri);

        // Execute the process.
        $response = $middleware->process($request, $handler);

        // Make assertions.
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function methodNotAllowed(): void
    {
        // Create mock services.
        $container = $this->createStub(ContainerInterface::class);
        $dispatcher = $this->createStub(Dispatcher::class);
        $event_dispatcher = $this->createStub(EventDispatcherInterface::class);
        $metadata_provider = $this->createStub(MetadataProvider::class);
        $response_factory = $this->createStub(ResponseFactoryInterface::class);
        $parameter_processor = $this->createStub(ParameterProcessor::class);

        // Configure the services.
        $dispatcher->method('dispatch')->willReturn([Dispatcher::METHOD_NOT_ALLOWED]);
        $response_factory->method('createResponse')->with(405)->willReturn(
            $this->createStub(ResponseInterface::class)
        );

        // Create the middleware.
        $middleware = new RoutingMiddleware(
            $container,
            $dispatcher,
            $event_dispatcher,
            $metadata_provider,
            $response_factory,
            $parameter_processor
        );

        // Create the values for processing.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $uri = $this->createStub(UriInterface::class);

        // Configure the values.
        $request->method('getUri')->willReturn($uri);

        // Execute the process.
        $response = $middleware->process($request, $handler);

        // Make assertions.
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function routeFound(): void
    {
        // Create mock services.
        $container = $this->createStub(ContainerInterface::class);
        $dispatcher = $this->createStub(Dispatcher::class);
        $event_dispatcher = $this->createStub(EventDispatcherInterface::class);
        $metadata_provider = $this->createStub(MetadataProvider::class);
        $response_factory = $this->createStub(ResponseFactoryInterface::class);
        $parameter_processor = $this->createStub(ParameterProcessor::class);

        // Configure the services.
        $dispatcher->method('dispatch')->willReturn([Dispatcher::FOUND, [UserController::class, 'login'], []]);
        $response_factory->method('createResponse')->with(404)->willReturn(
            $this->createStub(ResponseInterface::class)
        );
        $controller = $this->createStub(UserController::class);
        $container->method('get')->with(UserController::class)->willReturn($controller);

        // Create the middleware.
        $middleware = new RoutingMiddleware(
            $container,
            $dispatcher,
            $event_dispatcher,
            $metadata_provider,
            $response_factory,
            $parameter_processor
        );

        // Create the values for processing.
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $uri = $this->createStub(UriInterface::class);

        // Configure the values.
        $request->method('getUri')->willReturn($uri);

        // Execute the process.
        $response = $middleware->process($request, $handler);

        // Make assertions.
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
