<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\Grant\RefreshTokenGrant as OAuthRefreshTokenGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\Oidc\OidcTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use Lcobucci\JWT\Configuration;
use Psr\Http\Message\ServerRequestInterface;

class RefreshTokenGrant extends OAuthRefreshTokenGrant
{
    private Configuration $jwt;

    public function __construct(
        Configuration $jwt,
        RefreshTokenManagerInterface $refresh_token_manager,
        AccessTokenManagerInterface $access_token_manager,
        SecureStringService $secure_string_service,
        OAuthScopeRegistry $scope_registry,
        DateInterval $access_token_ttl = new DateInterval('P1H'),
        DateInterval $refresh_token_ttl = new DateInterval('P1M'),
    ) {
        $this->jwt = $jwt;
        parent::__construct(
            $refresh_token_manager,
            $access_token_manager,
            $secure_string_service,
            $scope_registry,
            $access_token_ttl,
            $refresh_token_ttl
        );
    }

    public function handleTokenRequest(ServerRequestInterface $request, ?AuthRequest $auth_request): OidcTokenResponse
    {
        // Call the parent method to handle the token request.
        $response = parent::handleTokenRequest($request, $auth_request);

        // If the scope includes 'openid', we need to ensure the ID token is included.
        $scopes = explode(' ', $auth_request->getScope());
        if (in_array('openid', $scopes, true)) {
            $now = new DateTimeImmutable();
            $id_token = $this->jwt->builder()
                ->issuedBy($request->getUri()->getScheme() . '://' . $request->getUri()->getHost())
                ->permittedFor($auth_request->getClientId())
                ->relatedTo($response->getAccessToken()->getUser()->getIdentifier())
                ->issuedAt($now)
                ->expiresAt($now->add($this->access_token_ttl))
                ->withClaim('nonce', $auth_request->getNonce())
                ->getToken($this->jwt->signer(), $this->jwt->signingKey());
        }

        // Return the response with the ID token added.
        return new OidcTokenResponse(
            $response->getAccessToken(),
            $response->getRefreshToken(),
            $id_token ?? null
        );
    }
}
