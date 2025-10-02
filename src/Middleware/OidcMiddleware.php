<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Security\Oidc\OidcServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OidcMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $redirect_uri,
        private ResponseFactoryInterface $rf,
        private StreamFactoryInterface $sf,
        private UriInterface $base_uri,
        private OidcServer $server,
        private string $auth_path = '/oauth/authorize',
        private string $token_path = '/oauth/token',
        private string $userinfo_path = '/oauth/userinfo',
        private string $jwks_path = '/oauth/jwks.json',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        switch ($request->getUri()->getPath()) {
            case $this->auth_path:
                return $this->server
                    ->handleAuthorizationRequest($request, $this->redirect_uri);
            case $this->token_path:
                return $this->server->handleTokenRequest($request);
            case $this->userinfo_path:
                return $this->server->handleUserInfoRequest($request);
                break;
            case $this->jwks_path:
                return $this->server->handleJwksRequest();
                break;
            case '/.well-known/openid-configuration':
                return $this->rf->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->sf->createStream(json_encode([
                        'issuer' => (string) $this->base_uri,
                        'authorization_endpoint' => (string) $this->base_uri->withPath($this->auth_path),
                        'token_endpoint' => (string) $this->base_uri->withPath($this->token_path),
                        'userinfo_endpoint' => (string) $this->base_uri->withPath($this->userinfo_path),
                        'jwks_uri' => (string) $this->base_uri->withPath($this->jwks_path),
                        'response_types_supported' => ['code', 'token', 'id_token'],
                        'subject_types_supported' => ['public'],
                        'id_token_signing_alg_values_supported' => ['RS256'],
                    ])));
        }

        // Delegate to next handler.
        return $handler->handle($request);
    }
}
