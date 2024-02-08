<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PreRouteDispatchEvent
{
    private ?ResponseInterface $response = null;

    public function __construct(
        private string $controller,
        private string $method,
        private ServerRequestInterface $request
    ) {
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
