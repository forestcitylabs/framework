<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AuthCodeInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use Psr\Http\Message\ServerRequestInterface;

class RefreshTokenGrant implements GrantInterface
{
    private RefreshTokenManagerInterface $refresh_token_manager;
    private AccessTokenManagerInterface $access_token_manager;
    private SecureStringService $secure_string_service;
    private OAuthScopeRegistry $scope_registry;
    protected DateInterval $access_token_ttl;
    protected DateInterval $refresh_token_ttl;

    public function __construct(
        RefreshTokenManagerInterface $refresh_token_manager,
        AccessTokenManagerInterface $access_token_manager,
        SecureStringService $secure_string_service,
        OAuthScopeRegistry $scope_registry,
        DateInterval $access_token_ttl = new DateInterval('P1H'),
        DateInterval $refresh_token_ttl = new DateInterval('P1M'),
    ) {
        $this->refresh_token_manager = $refresh_token_manager;
        $this->access_token_manager = $access_token_manager;
        $this->secure_string_service = $secure_string_service;
        $this->scope_registry = $scope_registry;
        $this->access_token_ttl = $access_token_ttl;
        $this->refresh_token_ttl = $refresh_token_ttl;
    }

    public function canHandleAuthorizationRequest(ServerRequestInterface $request): bool
    {
        // Refresh token grants do not handle authorization requests.
        return false;
    }

    public function handleAuthorizationRequest(ServerRequestInterface $request): AuthRequest
    {
        // Refresh token grants do not handle authorization requests.
        throw new \RuntimeException('Refresh token grant does not handle authorization requests.');
    }

    public function approveAuthorizationRequest(
        AuthRequest $auth_request,
        ServerRequestInterface $request,
        ?UserInterface $user
    ): AuthCodeInterface {
        // Refresh token grants do not approve authorization requests.
        throw new \RuntimeException('Refresh token grant does not approve authorization requests.');
    }

    public function canHandleTokenRequest(ServerRequestInterface $request): bool
    {
        // Check if the request is for a refresh token.
        return $request->getMethod() === 'POST' && $request->getParsedBody()['grant_type'] === 'refresh_token';
    }

    public function handleTokenRequest(ServerRequestInterface $request, ?AuthRequest $auth_request): OAuthTokenResponse
    {
        // Lookup the refresh token from the request.
        if (
            null === $old_refresh_token = $this->refresh_token_manager->findRefreshTokenByToken(
                $request->getParsedBody()['refresh_token']
            )
        ) {
            throw new OAuthException('Invalid refresh token.');
        }

        // Ensure the refresh token is not expired.
        if ($old_refresh_token->getExpiresAt() < new DateTimeImmutable()) {
            $this->refresh_token_manager->revokeRefreshToken($old_refresh_token);
            throw new OAuthException('Refresh token has expired.');
        }

        // Create a new access token.
        $access_token = $this->access_token_manager->generateAccessToken();
        $access_token->setUser($old_refresh_token->getUser());
        $access_token->setToken($this->secure_string_service->generateRandomString());
        $access_token->setExpiresAt((new DateTimeImmutable())->add($this->access_token_ttl));

        // Filter privileged scopes before adding to the new access token.
        foreach ($this->scope_registry->filterPrivilegedScopes($old_refresh_token->getScopes()) as $scope) {
            $access_token->addScope($scope);
        }

        // Persist the new access token.
        $this->access_token_manager->persistAccessToken($access_token);

        // Create a new refresh token.
        $refresh_token = $this->refresh_token_manager->generateRefreshToken();
        $refresh_token->setUser($old_refresh_token->getUser());
        $refresh_token->setToken($this->secure_string_service->generateRandomString());
        $refresh_token->setExpiresAt((new DateTimeImmutable())->add($this->refresh_token_ttl));

        // Add filtered scopes from the new access token.
        foreach ($access_token->getScopes() as $scope) {
            $refresh_token->addScope($scope);
        }

        // Persist the new refresh token.
        $this->refresh_token_manager->persistRefreshToken($refresh_token);

        // Revoke the old refresh token to prevent reuse.
        $this->refresh_token_manager->revokeRefreshToken($old_refresh_token);

        // Return the token response.
        return new OAuthTokenResponse($access_token, $refresh_token);
    }
}
