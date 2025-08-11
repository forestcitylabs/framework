<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc;

use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthServer;
use ForestCityLabs\Framework\Utility\EncryptionService;
use Lcobucci\JWT\Configuration;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class OidcServer extends OAuthServer
{
    protected Configuration $jwt;
    protected OidcClaimRegistry $claim_registry;

    public function __construct(
        Configuration $jwt,
        ResponseFactoryInterface $rf,
        StreamFactoryInterface $sf,
        EncryptionService $encryption_service,
        OAuthScopeRegistry $scope_registry,
        OidcClaimRegistry $claim_registry,
        array $grants = [],
        string $cookie_key = '_oauth_session',
    ) {
        $this->jwt = $jwt;
        $this->claim_registry = $claim_registry;
        parent::__construct($rf, $sf, $encryption_service, $scope_registry, $grants, $cookie_key);
    }

    public function handleUserInfoRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Handle user info request logic here.
        // This typically involves validating the access token and returning user information.
        // For now, we will return a placeholder response.
        return $this->rf->createResponse(200)
            ->withBody($this->sf->createStream(json_encode(['user' => 'info'])));
    }

    public function handleJwksRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Handle JWKS request logic here.
        $key = $this->jwt->verificationKey();
        $details = openssl_pkey_get_details(openssl_pkey_get_public($key->contents()));
        return $this->rf->createResponse(200)
            ->withBody($this->sf->createStream(json_encode(['keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'kid' => 'key',
                    'n' => $details['rsa']['n'],
                    'e' => $details['rsa']['e'],
                ]
            ]])));
    }

    public function validateScopes(array $scopes): void
    {
        foreach ($scopes as $scope) {
            if (
                !$this->scope_registry->isValidScope($scope)
                && !$this->claim_registry->isValidClaim($scope)
                && !$this->claim_registry->isValidGroup($scope)
            ) {
                throw new OAuthException(sprintf('Invalid scope: %s', $scope));
            }
        }
    }
}
