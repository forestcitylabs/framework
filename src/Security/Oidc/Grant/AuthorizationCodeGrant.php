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
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
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

    public function handleAuthorizationRequest(ServerRequestInterface $request): AuthRequest
    {
        // Call the parent method to handle the authorization request.
        $auth_request = parent::handleAuthorizationRequest($request);

        // Flatten the scopes by expanding groups to individual claims.
        $scopes = $this->flattenScopes($auth_request->getScope());
        $auth_request->setScope(implode(' ', $scopes));

        // If the scope includes 'openid' we need to include a nonce.
        if (in_array('openid', $scopes, true)) {
            $params = $request->getQueryParams();
            if (!isset($params['nonce']) || empty($params['nonce'])) {
                throw new OAuthException('Nonce is required for OpenID Connect authentication requests.');
            }
            $auth_request->setNonce($params['nonce']);
        }

        return $auth_request;
    }

    public function handleTokenRequest(ServerRequestInterface $request, ?AuthRequest $auth_request): OidcTokenResponse
    {
        // Call the parent method to handle the token request.
        $response = parent::handleTokenRequest($request, $auth_request);

        // If the scope includes 'openid', we need to ensure the ID token is included.
        $scopes = explode(' ', $auth_request->getScope());
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

    public function flattenScopes(string $scopes): array
    {
        // Flatten the scopes by expanding groups to individual claims.
        $flattened = [];
        foreach (explode(' ', $scopes) as $scope) {
            if ($this->claim_registry->isValidGroup($scope)) {
                $flattened = array_merge($flattened, $this->claim_registry->getGroup($scope));
            } else {
                $flattened[] = $scope;
            }
        }
        return array_unique($flattened);
    }
}
