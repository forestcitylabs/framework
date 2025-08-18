<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Security\Oidc\OidcServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OidcMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $redirect_uri,
        private ResponseFactoryInterface $rf,
        private StreamFactoryInterface $sf,
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
                    ->handleAuthorizationRequest($request)
                    ->withStatus(302)
                    ->withHeader('Location', $this->redirect_uri);
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
                    ->withBody($this->sf->createStream(json_encode([
                        'issuer' => $request->getUri()->getScheme() . '://' . $request->getUri()->getHost(),
                        'authorization_endpoint' => $request->getUri()->withPath($this->auth_path)->__toString(),
                        'token_endpoint' => $request->getUri()->withPath($this->token_path)->__toString(),
                        'userinfo_endpoint' => $request->getUri()->withPath($this->userinfo_path)->__toString(),
                        'jwks_uri' => $request->getUri()->withPath($this->jwks_path)->__toString(),
                        'response_types_supported' => ['code', 'token', 'id_token'],
                        'subject_types_supported' => ['public'],
                        'id_token_signing_alg_values_supported' => ['RS256'],
                    ])));
        }

        // Delegate to next handler.
        return $handler->handle($request);
    }
}
