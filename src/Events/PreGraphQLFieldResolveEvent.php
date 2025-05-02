<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Events;

use Psr\Http\Message\ServerRequestInterface;

class PreGraphQLFieldResolveEvent
{
    public function __construct(
        private mixed $context,
        private ServerRequestInterface $request,
        private array $args,
    ) {
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getArgs(): array
    {
        return $this->args;
    }
}
