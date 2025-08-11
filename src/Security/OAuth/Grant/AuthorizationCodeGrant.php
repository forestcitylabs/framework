<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\ClientManagerInterface;
use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\AuthCodeManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AuthCodeInterface;
use ForestCityLabs\Framework\Security\Model\ClientInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationCodeGrant implements GrantInterface
{
    protected AuthCodeManagerInterface $auth_code_manager;
    protected AccessTokenManagerInterface $access_token_manager;
    protected RefreshTokenManagerInterface $refresh_token_manager;
    protected ClientManagerInterface $client_manager;
    protected SecureStringService $secure_string_service;
    protected OAuthScopeRegistry $scope_registry;
    protected DateInterval $code_ttl;
    protected DateInterval $access_token_ttl;
    protected DateInterval $refresh_token_ttl;

    public function __construct(
        AuthCodeManagerInterface $auth_code_manager,
        AccessTokenManagerInterface $access_token_manager,
        RefreshTokenManagerInterface $refresh_token_manager,
        ClientManagerInterface $client_manager,
        SecureStringService $secure_string_service,
        OAuthScopeRegistry $scope_registry,
        DateInterval $code_ttl = new DateInterval('P5M'),
        DateInterval $access_token_ttl = new DateInterval('P1H'),
        DateInterval $refresh_token_ttl = new DateInterval('P1M'),
    ) {
        $this->auth_code_manager = $auth_code_manager;
        $this->access_token_manager = $access_token_manager;
        $this->refresh_token_manager = $refresh_token_manager;
        $this->client_manager = $client_manager;
        $this->secure_string_service = $secure_string_service;
        $this->scope_registry = $scope_registry;
        $this->code_ttl = $code_ttl;
        $this->access_token_ttl = $access_token_ttl;
        $this->refresh_token_ttl = $refresh_token_ttl;
    }

    public function canHandleAuthorizationRequest(ServerRequestInterface $request): bool
    {
        $params = $request->getQueryParams();
        if ($request->getMethod === 'GET' && isset($params['response_type']) && $params['response_type'] === 'code') {
            return true;
        }
        return false;
    }

    public function canHandleTokenRequest(ServerRequestInterface $request): bool
    {
        $params = $request->getQueryParams();
        if (
            $request->getMethod === 'POST'
            && isset($params['grant_type'])
            && $params['grant_type'] === 'authorization_code'
        ) {
            return true;
        }
        return false;
    }

    public function handleAuthorizationRequest(ServerRequestInterface $request): AuthRequest
    {
        // Validate the authorization request.
        $params = $request->getQueryParams();
        if ($params['response_type'] ?? null !== 'code') {
            throw new OAuthException('Invalid response type. Only "code" is supported.');
        }

        if (null === $client = $this->client_manager->findClientById($params['client_id'] ?? '')) {
            throw new OAuthException('Invalid client ID.');
        }

        if (!in_array($params['redirect_uri'] ?? null, $client->getRedirectUris(), true)) {
            throw new OAuthException('Invalid redirect URI.');
        }

        // If no scope is provided, use the default scopes of the client.
        if (!array_key_exists('scope', $params)) {
            $params['scope'] = implode(' ', $client->getScopes());
        }

        // Validate the scopes.
        $this->validateScopes(explode(' ', $params['scope'] ?? ''), $client);

        if (!isset($params['state']) || empty($params['state'])) {
            throw new OAuthException('State parameter is required.');
        }

        if (!$client->isConfidential() && !isset($params['code_challenge'])) {
            throw new OAuthException('Code challenge is required for public clients.');
        }

        if (isset($params['code_challenge'])) {
            if (!isset($params['code_challenge_method'])) {
                throw new OAuthException('Code challenge method is required when code challenge is provided.');
            }

            if ($params['code_challenge_method'] !== 'S256') {
                throw new OAuthException('Unsupported code challenge method. Only "S256" is supported.');
            }

            if (strlen($params['code_challenge']) != 43) {
                throw new OAuthException('Code challenge must be 43 characters long for S256.');
            }

            if (base64_decode($params['code_challenge']) === false) {
                throw new OAuthException('Code challenge must be a valid base64url encoded string.');
            }
        }

        // Create auth request.
        return new AuthRequest(
            $request->getQueryParams()['client_id'],
            $request->getQueryParams()['redirect_uri'],
            $request->getQueryParams()['response_type'],
            (new DateTimeImmutable())->add($this->code_ttl),
            $request->getQueryParams()['scope'] ?? null,
            $request->getQueryParams()['state'] ?? null,
            $request->getQueryParams()['code_challenge'] ?? null,
            $request->getQueryParams()['code_challenge_method'] ?? null
        );
    }

    public function approveAuthorizationRequest(
        AuthRequest $auth_request,
        ServerRequestInterface $request,
        ?array $granted_scopes = null,
        ?UserInterface $user = null,
    ): AuthCodeInterface {
        // Create authorization code.
        $code = $this->auth_code_manager->generateAuthCode();
        $code->setCode($this->secure_string_service->generateRandomString());
        $code->setClient($this->client_manager->findClientById($auth_request->getClientId()));
        $code->setExpiresAt(new DateTimeImmutable($auth_request->getExpiresAt()->format('Y-m-d H:i:s')));
        $code->setUser($user);

        // Get the requested scopes.
        $requested_scopes = explode(' ', $auth_request['scope']);

        // If no scopes are granted, use the scopes from the auth request.
        if (null === $granted_scopes) {
            $granted_scopes = $requested_scopes;
        }

        foreach ($granted_scopes as $scope) {
            // Validate the scope.
            if (!in_array($scope, $requested_scopes, true)) {
                throw new OAuthException(sprintf('Scope "%s" is not allowed for this authorization request.', $scope));
            }
            $code->addScope($scope);
        }

        // Create a response with the authorization code.
        $this->auth_code_manager->persistAuthCode($code);
        return $code;
    }

    public function handleTokenRequest(
        ServerRequestInterface $request,
        ?AuthRequest $auth_request
    ): OAuthTokenResponse {
        $params = $request->getParsedBody();

        // The code and client id are required parameters.
        if (!isset($params['code']) || !isset($params['client_id'])) {
            throw new OAuthException('Missing required parameters');
        }

        // Lookup the client.
        if (null === $client = $this->client_manager->findClientById($params['client_id'])) {
            throw new OAuthException('Invalid client ID');
        }

        // If the client is confidential, the client secret is also required.
        if ($client->isConfidential()) {
            if (!isset($params['client_secret'])) {
                throw new OAuthException('Missing client secret');
            }

            // Validate client credentials.
            if (!$this->client_manager->validateClientCredentials($params['client_id'], $params['client_secret'])) {
                throw new OAuthException('Invalid client credentials');
            }
        } else {
            // If the auth request was made with a code challenge, validate it.
            if (!isset($params['code_verifier'])) {
                throw new OAuthException('Missing code verifier');
            }

            // Validate the code verifier against the code challenge.
            $expected_challenge = base64_encode(
                hash('sha256', $params['code_verifier'], true)
            );
            if ($expected_challenge !== $auth_request['code_challenge']) {
                throw new OAuthException('Invalid code verifier');
            }
        }

        // Find the auth code.
        $auth_code = $this->auth_code_manager->findAuthCode($params['code']);
        if (null === $auth_code || $auth_code->getClient()->getId() !== $params['client_id']) {
            throw new OAuthException('Invalid authorization code');
        }

        // Check if the auth code is expired.
        if ($auth_code->getExpiresAt() < new DateTimeImmutable()) {
            $this->auth_code_manager->revokeAuthCode($auth_code);
            throw new OAuthException('Authorization code has expired');
        }

        // Create access token and persist it.
        $access_token = $this->access_token_manager->generateAccessToken();
        $access_token->setUser($auth_code->getUser());
        $access_token->setToken($this->secure_string_service->generateRandomString());
        $access_token->setExpiresAt((new DateTimeImmutable())->add($this->access_token_ttl));
        foreach ($auth_code->getScopes() as $scope) {
            $access_token->addScope($scope);
        }
        $this->access_token_manager->persistAccessToken($access_token);

        // Create a refresh token and persist it.
        $refresh_token = $this->refresh_token_manager->generateRefreshToken();
        $refresh_token->setUser($auth_code->getUser());
        $refresh_token->setToken($this->secure_string_service->generateRandomString());
        $refresh_token->setExpiresAt((new DateTimeImmutable())->add($this->refresh_token_ttl));
        foreach ($auth_code->getScopes() as $scope) {
            $refresh_token->addScope($scope);
        }
        $this->refresh_token_manager->persistRefreshToken($refresh_token);

        // Revoke the auth code.
        $this->auth_code_manager->revokeAuthCode($auth_code);

        // Return the token response.
        return new OAuthTokenResponse($access_token, $refresh_token);
    }

    public function validateScopes(array $scopes, ClientInterface $client): void
    {
        // Validate the requested scopes against the registered scopes.
        foreach ($scopes as $scope) {
            if (!$this->scope_registry->isValidScope($scope)) {
                throw new OAuthException("Invalid scope: $scope");
            }
            if (!in_array($scope, $client->getScopes(), true)) {
                throw new OAuthException(sprintf('Invalid scope "%s" for client.', $scope));
            }
        }
    }
}
