<?php

namespace ForestCityLabs\Framework\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class PostMiddlewareHandleEvent
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestInterface $request,
        private ResponseInterface $response
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

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
