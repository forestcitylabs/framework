<?php

namespace ForestCityLabs\Framework;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Kernel implements RequestHandlerInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function addMiddleware(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Process the request using middlewares.
        $middleware = $this->container->get(array_shift($this->middleware));

        return $middleware->process($request, $this);
    }
}
