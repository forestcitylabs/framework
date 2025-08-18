<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc;

use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthServer;
use ForestCityLabs\Framework\Utility\EncryptionService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class OidcServer extends OAuthServer
{
    public function __construct(
        protected AccessTokenManagerInterface $access_token_manager,
        protected ClaimResolver $claim_resolver,
        protected Keystore $keystore,
        protected OidcClaimRegistry $claim_registry,
        ResponseFactoryInterface $rf,
        StreamFactoryInterface $sf,
        EncryptionService $encryption_service,
        OAuthScopeRegistry $scope_registry,
        array $grants = [],
        string $cookie_key = '_oauth_session',
    ) {
        parent::__construct(
            $rf,
            $sf,
            $encryption_service,
            $scope_registry,
            $grants,
            $cookie_key
        );
    }

    public function handleUserInfoRequest(ServerRequestInterface $request): ResponseInterface
    {
        // Check if the request has a valid access token.
        if ($request->hasHeader('Authorization')) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/^Bearer\s+(\S+)$/', $authHeader, $matches)) {
                $token = $matches[1];
                if (null === $access_token = $this->access_token_manager->findAccessToken($token)) {
                    return $this->rf->createResponse(401)
                        ->withBody($this->sf->createStream(json_encode(['error' => 'invalid_token'])))
                        ->withHeader('Content-Type', 'application/json');
                }

                $claims = [];
                foreach (
                    $this->claim_resolver->resolveClaims(
                        $access_token->getScopes(),
                        $access_token->getUser()
                    ) as $claim => $value
                ) {
                    $claims[$claim] = $value;
                }

                return $this->rf->createResponse(200)
                    ->withBody($this->sf->createStream(json_encode($claims, JSON_THROW_ON_ERROR)))
                    ->withHeader('Content-Type', 'application/json');
            }
        }
        return $this->rf->createResponse(200)
            ->withBody($this->sf->createStream(json_encode(['user' => 'info'])));
    }

    public function handleJwksRequest(): ResponseInterface
    {
        $keys = [];
        foreach ($this->keystore->getKeys() as $name => $key) {
            $details = openssl_pkey_get_details(openssl_pkey_get_public($key['public']));
            $keys[] = [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $name,
                'n' => base64_encode($details['rsa']['n']),
                'e' => base64_encode($details['rsa']['e']),
            ];
        }
        return $this->rf->createResponse(200)
            ->withBody($this->sf->createStream(json_encode($keys, JSON_THROW_ON_ERROR)))
            ->withHeader('Content-Type', 'application/json');
    }
}
