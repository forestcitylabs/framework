<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\ClientManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\Grant\RefreshTokenGrant as OAuthRefreshTokenGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\Oidc\ClaimResolver;
use ForestCityLabs\Framework\Security\Oidc\OidcTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use Lcobucci\JWT\Configuration;
use Psr\Http\Message\ServerRequestInterface;

class RefreshTokenGrant extends OAuthRefreshTokenGrant
{
    public function __construct(
        protected Configuration $jwt,
        protected ClaimResolver $claim_resolver,
        RefreshTokenManagerInterface $refresh_token_manager,
        AccessTokenManagerInterface $access_token_manager,
        SecureStringService $secure_string_service,
        OAuthScopeRegistry $scope_registry,
        ClientManagerInterface $client_manager,
        DateInterval $access_token_ttl = new DateInterval('PT1H'),
        DateInterval $refresh_token_ttl = new DateInterval('P1M'),
    ) {
        parent::__construct(
            $refresh_token_manager,
            $access_token_manager,
            $secure_string_service,
            $scope_registry,
            $client_manager,
            $access_token_ttl,
            $refresh_token_ttl
        );
    }

    public function handleTokenRequest(ServerRequestInterface $request, ?AuthRequest $auth_request): OidcTokenResponse
    {
        // Call the parent method to handle the token request.
        $response = parent::handleTokenRequest($request, $auth_request);

        $scopes = $response->getAccessToken()->getScopes();
        if (in_array('openid', $scopes, true)) {
            // Get the current time.
            $now = new DateTimeImmutable();

            // Get the client from the auth request.
            $client = $this->client_manager->findClientById($auth_request->getClientId());

            // Start building the ID token.
            $builder = $this->jwt->builder()
                ->issuedBy($request->getUri()->getScheme() . '://' . $request->getUri()->getHost())
                ->permittedFor($auth_request->getClientId())
                ->relatedTo($response->getAccessToken()->getUser()->getIdentifier())
                ->issuedAt($now)
                ->expiresAt($now->add($this->access_token_ttl))
                ->withClaim('nonce', $auth_request->getNonce());

            // Add allowed claims to the ID token.
            foreach (
                $this->claim_resolver->resolveClaims(
                    $scopes,
                    $response->getAccessToken()->getUser()
                ) as $claim => $value
            ) {
                if (in_array($claim, $client->getScopes())) {
                    $builder = $builder->withClaim($claim, $value);
                }
            }

            $id_token = $builder->getToken(
                $this->jwt->signer(),
                $this->jwt->signingKey()
            );
        }

        // Return the response with the ID token added.
        return new OidcTokenResponse(
            $response->getAccessToken(),
            $response->getRefreshToken(),
            $id_token ?? null
        );
    }
}
