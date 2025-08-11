<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Security\OAuth\OidcServer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OidcMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $redirect_path,
        private ResponseFactoryInterface $rf,
        private StreamFactoryInterface $sf,
        private OidcServer $oidc_server,
        private string $encryption_key,
        private string $cookie_key = '_oauth_session',
        private string $auth_path = '/authorize',
        private string $token_path = '/token',
        private string $userinfo_path = '/userinfo',
        private string $jwks_path = '/jwks.json',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        switch ($request->getUri()->getPath()) {
            case $this->auth_path:
                // Create auth request and redirect to the application.
                return $this->oidc_server
                    ->handleAuthorizationRequest($request)
                    ->withStatus(302)
                    ->withHeader('Location', $this->redirect_path);
            case $this->token_path:
                // Handle token request
                return $this->oidc_server->handleTokenRequest($request);
            case $this->userinfo_path:
                // Handle user info request
                // Validate the access token and return user information.
                // This is typically where you would implement the UserInfo endpoint.
                break;
            case $this->jwks_path:
                // Handle JWKS request
                // Return the JSON Web Key Set (JWKS) for public keys used to verify JWTs.
                // This is where you would implement the JWKS endpoint.
                break;
            case '/.well-known/openid-configuration':
                // Handle OpenID Connect discovery request
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
