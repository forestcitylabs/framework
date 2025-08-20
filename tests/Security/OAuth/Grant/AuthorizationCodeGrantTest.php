<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\OAuth\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\AuthCodeManagerInterface;
use ForestCityLabs\Framework\Security\Manager\ClientManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\AuthCodeInterface;
use ForestCityLabs\Framework\Security\Model\ClientInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\Grant\AuthorizationCodeGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(AuthorizationCodeGrant::class)]
#[Group('security')]
#[Group('oauth')]
#[UsesClass(AuthRequest::class)]
#[UsesClass(OAuthTokenResponse::class)]
class AuthorizationCodeGrantTest extends TestCase
{
    private AuthCodeManagerInterface $authCodeManager;
    private AccessTokenManagerInterface $accessTokenManager;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private ClientManagerInterface $clientManager;
    private SecureStringService $secureStringService;
    private OAuthScopeRegistry $scopeRegistry;
    private AuthorizationCodeGrant $grant;

    protected function setUp(): void
    {
        $this->authCodeManager = $this->createMock(AuthCodeManagerInterface::class);
        $this->accessTokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->clientManager = $this->createMock(ClientManagerInterface::class);
        $this->secureStringService = $this->createMock(SecureStringService::class);
        $this->scopeRegistry = $this->createMock(OAuthScopeRegistry::class);

        $this->grant = new AuthorizationCodeGrant(
            $this->authCodeManager,
            $this->accessTokenManager,
            $this->refreshTokenManager,
            $this->clientManager,
            $this->secureStringService,
            $this->scopeRegistry
        );
    }

    public function testCanHandleAuthorizationRequestWithValidRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getQueryParams')->willReturn([
            'response_type' => 'code',
            'client_id' => 'test'
        ]);

        $this->assertTrue($this->grant->canHandleAuthorizationRequest($request));
    }

    public function testCanHandleAuthorizationRequestWithInvalidMethod(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getQueryParams')->willReturn([
            'response_type' => 'code',
            'client_id' => 'test'
        ]);

        $this->assertFalse($this->grant->canHandleAuthorizationRequest($request));
    }

    public function testCanHandleAuthorizationRequestWithInvalidResponseType(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getQueryParams')->willReturn([
            'response_type' => 'token',
            'client_id' => 'test'
        ]);

        $this->assertFalse($this->grant->canHandleAuthorizationRequest($request));
    }

    public function testCanHandleTokenRequestWithValidRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getParsedBody')->willReturn(['grant_type' => 'authorization_code']);

        $this->assertTrue($this->grant->canHandleTokenRequest($request));
    }

    public function testCanHandleTokenRequestWithInvalidMethod(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getParsedBody')->willReturn(['grant_type' => 'authorization_code']);

        $this->assertFalse($this->grant->canHandleTokenRequest($request));
    }

    public function testCanHandleTokenRequestWithInvalidGrantType(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getParsedBody')->willReturn(['grant_type' => 'client_credentials']);

        $this->assertFalse($this->grant->canHandleTokenRequest($request));
    }

    public function testHandleAuthorizationRequestWithValidParams(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['read', 'write']);
        $client->method('isConfidential')->willReturn(true);

        $this->clientManager->method('findClientById')
            ->with('test_client')
            ->willReturn($client);

        $this->scopeRegistry->method('isValidScope')
            ->willReturn(true);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'read write',
            'state' => 'random_state'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        $authRequest = $this->grant->handleAuthorizationRequest($request);

        $this->assertInstanceOf(AuthRequest::class, $authRequest);
        $this->assertEquals('test_client', $authRequest->getClientId());
        $this->assertEquals('https://example.com/callback', $authRequest->getRedirectUri());
        $this->assertEquals('code', $authRequest->getResponseType());
        $this->assertEquals('read write', $authRequest->getScope());
        $this->assertEquals('random_state', $authRequest->getState());
    }

    public function testHandleAuthorizationRequestWithInvalidResponseType(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid response type. Only "code" is supported.');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['response_type' => 'token']);

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestWithInvalidClientId(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid client ID.');

        $this->clientManager->method('findClientById')
            ->willReturn(null);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'invalid_client'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestWithInvalidRedirectUri(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid redirect URI.');

        $client = $this->createMock(ClientInterface::class);
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);

        $this->clientManager->method('findClientById')
            ->willReturn($client);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://evil.com/callback'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestWithMissingState(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('State parameter is required.');

        $client = $this->createMock(ClientInterface::class);
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['read']);

        $this->clientManager->method('findClientById')
            ->willReturn($client);

        $this->scopeRegistry->method('isValidScope')
            ->willReturn(true);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestPublicClientWithoutCodeChallenge(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Code challenge is required for public clients.');

        $client = $this->createMock(ClientInterface::class);
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['read']);
        $client->method('isConfidential')->willReturn(false);

        $this->clientManager->method('findClientById')
            ->willReturn($client);

        $this->scopeRegistry->method('isValidScope')
            ->willReturn(true);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'state' => 'random_state'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestWithCodeChallengeButNoMethod(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Code challenge method is required when code challenge is provided.');

        $client = $this->createMock(ClientInterface::class);
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['read']);
        $client->method('isConfidential')->willReturn(false);

        $this->clientManager->method('findClientById')
            ->willReturn($client);

        $this->scopeRegistry->method('isValidScope')
            ->willReturn(true);

        $queryParams = [
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'state' => 'random_state',
            'code_challenge' => 'valid_challenge_string_with_43_characters'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($queryParams);

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testApproveAuthorizationRequestSuccess(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $user = $this->createMock(UserInterface::class);
        $authCode = $this->createMock(AuthCodeInterface::class);

        $this->clientManager->method('findClientById')
            ->willReturn($client);

        $this->authCodeManager->method('generateAuthCode')
            ->willReturn($authCode);

        $this->secureStringService->method('generateRandomString')
            ->willReturn('random_code');

        $authRequest = new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            new DateTimeImmutable('+5 minutes'),
            'read write'
        );

        $request = $this->createMock(ServerRequestInterface::class);

        $result = $this->grant->approveAuthorizationRequest($authRequest, $request, ['read'], $user);

        $this->assertSame($authCode, $result);
    }

    public function testHandleTokenRequestSuccess(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');

        $authCode = $this->createMock(AuthCodeInterface::class);
        $authCode->method('getClient')->willReturn($client);
        $authCode->method('getExpiresAt')->willReturn(new DateTimeImmutable('+5 minutes'));
        $authCode->method('getUser')->willReturn($this->createMock(UserInterface::class));
        $authCode->method('getScopes')->willReturn(['read', 'write']);

        $accessToken = $this->createMock(AccessTokenInterface::class);
        $refreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->clientManager->method('findClientById')
            ->willReturn($client);
        $this->clientManager->method('validateClientCredentials')
            ->willReturn(true);

        $this->authCodeManager->method('findAuthCode')
            ->willReturn($authCode);

        $this->accessTokenManager->method('generateAccessToken')
            ->willReturn($accessToken);

        $this->refreshTokenManager->method('generateRefreshToken')
            ->willReturn($refreshToken);

        $this->secureStringService->method('generateRandomString')
            ->willReturn('random_token');

        $params = [
            'grant_type' => 'authorization_code',
            'code' => 'auth_code',
            'client_id' => 'test_client',
            'client_secret' => 'client_secret'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($params);

        $result = $this->grant->handleTokenRequest($request, null);

        $this->assertInstanceOf(OAuthTokenResponse::class, $result);
    }

    public function testHandleTokenRequestWithMissingCode(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Missing required parameters');

        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => 'test_client'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($params);

        $this->grant->handleTokenRequest($request, null);
    }

    public function testHandleTokenRequestWithInvalidClient(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid client ID');

        $this->clientManager->method('findClientById')
            ->willReturn(null);

        $params = [
            'grant_type' => 'authorization_code',
            'code' => 'auth_code',
            'client_id' => 'invalid_client'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($params);

        $this->grant->handleTokenRequest($request, null);
    }

    public function testHandleTokenRequestConfidentialClientWithoutSecret(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Missing client secret');

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);

        $this->clientManager->method('findClientById')
            ->willReturn($client);

        $params = [
            'grant_type' => 'authorization_code',
            'code' => 'auth_code',
            'client_id' => 'test_client'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($params);

        $this->grant->handleTokenRequest($request, null);
    }

    public function testHandleTokenRequestWithExpiredAuthCode(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Authorization code has expired');

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');

        $authCode = $this->createMock(AuthCodeInterface::class);
        $authCode->method('getClient')->willReturn($client);
        $authCode->method('getExpiresAt')->willReturn(new DateTimeImmutable('-1 minute'));

        $this->clientManager->method('findClientById')
            ->willReturn($client);
        $this->clientManager->method('validateClientCredentials')
            ->willReturn(true);

        $this->authCodeManager->method('findAuthCode')
            ->willReturn($authCode);

        $params = [
            'grant_type' => 'authorization_code',
            'code' => 'expired_code',
            'client_id' => 'test_client',
            'client_secret' => 'client_secret'
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn($params);

        $this->grant->handleTokenRequest($request, null);
    }

    public function testValidateScopesWithInvalidScope(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid scope: invalid_scope');

        $client = $this->createMock(ClientInterface::class);
        $client->method('getScopes')->willReturn(['read', 'write']);

        $this->scopeRegistry->method('isValidScope')
            ->with('invalid_scope')
            ->willReturn(false);

        $this->grant->validateScopes(['invalid_scope'], $client);
    }

    public function testValidateScopesWithScopeNotAllowedForClient(): void
    {
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid scope "admin" for client.');

        $client = $this->createMock(ClientInterface::class);
        $client->method('getScopes')->willReturn(['read', 'write']);

        $this->scopeRegistry->method('isValidScope')
            ->willReturn(true);

        $this->grant->validateScopes(['admin'], $client);
    }

    public function testValidateScopesSuccess(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('getScopes')->willReturn(['read', 'write']);

        $this->scopeRegistry->method('isValidScope')
            ->willReturn(true);

        $this->grant->validateScopes(['read', 'write'], $client);

        $this->addToAssertionCount(1);
    }
}
