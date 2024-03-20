<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Middleware\FallbackControllerMiddleware;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(FallbackControllerMiddleware::class)]
class FallbackControllerMiddlewareTest extends TestCase
{
    #[Test]
    public function process404(): void
    {
        $container = $this->createConfiguredStub(ContainerInterface::class, [
            'get' => new UserController($this->createMock(ResponseFactoryInterface::class)),
        ]);
        $processor = $this->createStub(ParameterProcessor::class);
        $middleware = new FallbackControllerMiddleware(UserController::class, 'login', $container, $processor);

        $request = $this->createConfiguredStub(ServerRequestInterface::class, [
            'getMethod' => 'GET',
        ]);
        $response = $this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 404,
        ]);
        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    #[Test]
    public function process200(): void
    {
        $container = $this->createConfiguredStub(ContainerInterface::class, [
            'get' => new UserController($this->createMock(ResponseFactoryInterface::class)),
        ]);
        $processor = $this->createStub(ParameterProcessor::class);
        $middleware = new FallbackControllerMiddleware(UserController::class, 'login', $container, $processor);

        $request = $this->createConfiguredStub(ServerRequestInterface::class, [
            'getMethod' => 'GET',
        ]);
        $response = $this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
        ]);
        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $result = $middleware->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
