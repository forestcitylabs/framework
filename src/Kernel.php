<?php

namespace ForestCityLabs\Framework;

use ForestCityLabs\Framework\Events\PostMiddlewareHandleEvent;
use ForestCityLabs\Framework\Events\PreMiddlewareHandleEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class Kernel implements RequestHandlerInterface
{
    private array $middleware = [];

    public function __construct(
        private ContainerInterface $container,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger
    ) {
    }

    public function addMiddleware(string $middleware): void
    {
        $this->logger->info(sprintf('Adding middleware "%s".', $middleware), ['class' => $this::class]);
        $this->middleware[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get the middleware for handling this request.
        $middleware = $this->container->get(array_shift($this->middleware));
        $this->logger->info(sprintf('Processing request using middleware "%s"', $middleware::class), ['class' => $this::class]);

        // Handle the request, allowing other processes to track it.
        $this->dispatcher->dispatch(new PreMiddlewareHandleEvent($middleware, $request));
        $result = $middleware->process($request, $this);
        $this->dispatcher->dispatch(new PostMiddlewareHandleEvent($middleware, $request, $result));

        // Return the result.
        return $result;
    }
}
