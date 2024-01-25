<?php

namespace ForestCityLabs\Framework\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class PreMiddlewareHandleEvent
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestInterface $request
    ) {
    }

    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
