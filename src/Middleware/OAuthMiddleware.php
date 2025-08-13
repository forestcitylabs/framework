<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Security\OAuth\OAuthServer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private OAuthServer $server,
        private string $auth_path = '/oauth/authorize',
        private string $token_path = '/oauth/token'
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        switch ($request->getUri()->getPath()) {
            case $this->auth_path:
                return $this->server->handleAuthorizationRequest($request);
            case $this->token_path:
                return $this->server->handleTokenRequest($request);
        }

        return $handler->handle($request);
    }
}
