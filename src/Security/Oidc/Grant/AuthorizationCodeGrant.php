<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\AuthCodeManagerInterface;
use ForestCityLabs\Framework\Security\Manager\ClientManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\ClientInterface;
use ForestCityLabs\Framework\Security\OAuth\Grant\AuthorizationCodeGrant as OAuthAuthorizationCodeGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\Oidc\ClaimResolver;
use ForestCityLabs\Framework\Security\Oidc\OidcClaimRegistry;
use ForestCityLabs\Framework\Security\Oidc\OidcTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use Lcobucci\JWT\Configuration;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationCodeGrant extends OAuthAuthorizationCodeGrant
{
    public function __construct(
        protected Configuration $jwt,
        protected OidcClaimRegistry $claim_registry,
        protected ClaimResolver $claim_resolver,
        AuthCodeManagerInterface $auth_code_manager,
        AccessTokenManagerInterface $access_token_manager,
        RefreshTokenManagerInterface $refresh_token_manager,
        ClientManagerInterface $client_manager,
        SecureStringService $secure_string_service,
        OAuthScopeRegistry $scope_registry,
        DateInterval $code_ttl = new DateInterval('PT5M'),
        DateInterval $access_token_ttl = new DateInterval('PT1H'),
        DateInterval $refresh_token_ttl = new DateInterval('P1M'),
    ) {
        parent::__construct(
            $auth_code_manager,
            $access_token_manager,
            $refresh_token_manager,
            $client_manager,
            $secure_string_service,
            $scope_registry,
            $code_ttl,
            $access_token_ttl,
            $refresh_token_ttl
        );
    }

    public function handleTokenRequest(ServerRequestInterface $request): OidcTokenResponse
    {
        // Get auth code and create access and refresh tokens.
        $auth_code = $this->validateTokenRequest($request);
        $access_token = $this->generateAccessToken($auth_code);
        $refresh_token = $this->generateRefreshToken($auth_code);

        // If the scope includes 'openid', we need to ensure the ID token is included.
        $scopes = $this->flattenScopes($access_token->getScopes());
        if (in_array('openid', $scopes, true)) {
            // Get the current time.
            $now = new DateTimeImmutable();

            // Get the client from the auth request.
            $client = $auth_code->getClient();

            // Start building the ID token.
            $builder = $this->jwt->builder()
                ->issuedBy($request->getUri()->getScheme() . '://' . $request->getUri()->getHost())
                ->permittedFor($client->getIdentifier())
                ->relatedTo($access_token->getUser()->getIdentifier())
                ->issuedAt($now)
                ->expiresAt($now->add($this->access_token_ttl));

            if (null !== $nonce = $auth_code->getNonce()) {
                $builder->withClaim('nonce', $nonce);
            }

            // Add allowed claims to the ID token.
            foreach (
                $this->claim_resolver->resolveClaims(
                    $scopes,
                    $access_token->getUser()
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

        // Revoke the auth code.
        $this->auth_code_manager->revokeAuthCode($auth_code);

        // Return the response with the ID token added.
        return new OidcTokenResponse(
            $access_token,
            $refresh_token,
            $id_token ?? null
        );
    }

    public function validateScopes(array $scopes, ClientInterface $client): void
    {
        // Validate the requested scopes against the registered scopes.
        foreach ($scopes as $scope) {
            if (
                !$this->scope_registry->isValidScope($scope)
                && !$this->claim_registry->isValidClaim($scope)
                && !$this->claim_registry->isValidGroup($scope)
            ) {
                throw new OAuthException("Invalid scope: $scope");
            }
            if (!in_array($scope, array_merge($client->getScopes(), $this->claim_registry->getGroups()), true)) {
                throw new OAuthException(sprintf('Invalid scope "%s" for client.', $scope));
            }
        }
    }

    public function flattenScopes(array $scopes): array
    {
        // Flatten the scopes by expanding groups to individual claims.
        $flattened = [];
        foreach ($scopes as $scope) {
            if ($this->claim_registry->isValidGroup($scope)) {
                $flattened = array_merge($flattened, $this->claim_registry->getGroup($scope));
            } else {
                $flattened[] = $scope;
            }
        }
        return array_unique($flattened);
    }
}
