<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc\Grant;

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
use ForestCityLabs\Framework\Security\OAuth\Grant\AuthorizationCodeGrant as OAuthAuthorizationCodeGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Security\Oidc\ClaimResolver;
use ForestCityLabs\Framework\Security\Oidc\Grant\AuthorizationCodeGrant;
use ForestCityLabs\Framework\Security\Oidc\OidcClaimRegistry;
use ForestCityLabs\Framework\Security\Oidc\OidcTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthorizationCodeGrant::class)]
#[Group('security')]
#[Group('oidc')]
#[UsesClass(OAuthAuthorizationCodeGrant::class)]
#[UsesClass(AuthRequest::class)]
#[UsesClass(OidcTokenResponse::class)]
#[UsesClass(OAuthTokenResponse::class)]
class AuthorizationCodeGrantTest extends TestCase
{
    private AuthCodeManagerInterface $authCodeManager;
    private AccessTokenManagerInterface $accessTokenManager;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private ClientManagerInterface $clientManager;
    private SecureStringService $secureStringService;
    private OAuthScopeRegistry $scopeRegistry;
    private Configuration $jwtConfig;
    private OidcClaimRegistry $claimRegistry;
    private ClaimResolver $claimResolver;
    private AuthorizationCodeGrant $grant;

    protected function setUp(): void
    {
        $this->authCodeManager = $this->createMock(AuthCodeManagerInterface::class);
        $this->accessTokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->clientManager = $this->createMock(ClientManagerInterface::class);
        $this->secureStringService = $this->createMock(SecureStringService::class);
        $this->scopeRegistry = $this->createMock(OAuthScopeRegistry::class);
        $this->claimRegistry = $this->createMock(OidcClaimRegistry::class);
        $this->claimResolver = $this->createMock(ClaimResolver::class);

        // Create JWT configuration for testing
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('test-secret-key-that-is-longer-than-what-is-required')
        );

        $this->grant = new AuthorizationCodeGrant(
            $this->jwtConfig,
            $this->claimRegistry,
            $this->claimResolver,
            $this->authCodeManager,
            $this->accessTokenManager,
            $this->refreshTokenManager,
            $this->clientManager,
            $this->secureStringService,
            $this->scopeRegistry
        );
    }

    public function testConstructorExtendsOAuthGrant(): void
    {
        $this->assertInstanceOf(OAuthAuthorizationCodeGrant::class, $this->grant);
    }

    public function testHandleAuthorizationRequestWithValidOpenIdRequest(): void
    {
        $request = new ServerRequest('GET', 'https://example.com/auth', [], null, '1.1', [
            'QUERY_STRING' => 'response_type=code&client_id=test_client&redirect_uri=https://example.com/callback&scope=openid+profile&nonce=test_nonce'
        ]);
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'openid profile',
            'nonce' => 'test_nonce',
            'state' => 'test_state'
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['openid', 'profile', 'email']);
        $client->method('isConfidential')->willReturn(true);

        $this->clientManager->method('findClientById')
            ->with('test_client')
            ->willReturn($client);

        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => in_array($scope, ['openid', 'profile', 'email']));

        $this->claimRegistry->method('isValidClaim')
            ->willReturnCallback(fn($claim) => in_array($claim, ['name', 'email', 'sub']));

        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => $group === 'profile');

        $this->claimRegistry->method('getGroup')
            ->with('profile')
            ->willReturn(['name', 'given_name', 'family_name']);

        $this->claimRegistry->method('getGroups')
            ->willReturn(['profile']);

        $authRequest = $this->grant->handleAuthorizationRequest($request);

        $this->assertInstanceOf(AuthRequest::class, $authRequest);
        $this->assertEquals('test_nonce', $authRequest->getNonce());
        $this->assertEquals('test_client', $authRequest->getClientId());
        $this->assertEquals('https://example.com/callback', $authRequest->getRedirectUri());

        // Verify scopes were flattened
        $scopes = explode(' ', $authRequest->getScope());
        $this->assertContains('openid', $scopes);
        $this->assertContains('name', $scopes);
        $this->assertContains('given_name', $scopes);
        $this->assertContains('family_name', $scopes);
    }

    public function testHandleAuthorizationRequestThrowsExceptionWithoutNonce(): void
    {
        $request = new ServerRequest('GET', 'https://example.com/auth', [], null, '1.1', [
            'QUERY_STRING' => 'response_type=code&client_id=test_client&redirect_uri=https://example.com/callback&scope=openid+profile'
        ]);
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'openid profile',
            'state' => 'test_state'
            // Missing nonce
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['openid', 'profile']);

        $this->clientManager->method('findClientById')->willReturn($client);
        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => in_array($scope, ['openid', 'profile']));
        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => $group === 'profile');
        $this->claimRegistry->method('getGroup')
            ->with('profile')
            ->willReturn(['name', 'given_name', 'family_name']);
        $this->claimRegistry->method('getGroups')->willReturn(['profile']);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Nonce is required for OpenID Connect authentication requests.');

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestWithEmptyNonce(): void
    {
        $request = new ServerRequest('GET', 'https://example.com/auth');
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'openid profile',
            'nonce' => '', // Empty nonce
            'state' => 'test_state'
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['openid', 'profile']);

        $this->clientManager->method('findClientById')->willReturn($client);
        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => in_array($scope, ['openid', 'profile']));
        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => $group === 'profile');
        $this->claimRegistry->method('getGroup')
            ->with('profile')
            ->willReturn(['name', 'given_name', 'family_name']);
        $this->claimRegistry->method('getGroups')->willReturn(['profile']);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Nonce is required for OpenID Connect authentication requests.');

        $this->grant->handleAuthorizationRequest($request);
    }

    public function testHandleAuthorizationRequestWithoutOpenIdScope(): void
    {
        $request = new ServerRequest('GET', 'https://example.com/auth');
        $request = $request->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test_client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'profile email', // No openid scope, so nonce not required
            'state' => 'test_state'
        ]);

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');
        $client->method('getRedirectUris')->willReturn(['https://example.com/callback']);
        $client->method('getScopes')->willReturn(['profile', 'email']);

        $this->clientManager->method('findClientById')->willReturn($client);
        $this->scopeRegistry->method('isValidScope')->willReturn(true);
        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')->willReturn(true);
        $this->claimRegistry->method('getGroup')->willReturn(['name', 'email']);
        $this->claimRegistry->method('getGroups')->willReturn(['profile']);

        $authRequest = $this->grant->handleAuthorizationRequest($request);

        $this->assertInstanceOf(AuthRequest::class, $authRequest);
        $this->assertNull($authRequest->getNonce());
    }

    public function testHandleTokenRequestWithOpenIdScopeGeneratesIdToken(): void
    {
        $tokenRequest = new ServerRequest('POST', 'https://example.com/token');
        $tokenRequest = $tokenRequest->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'test_auth_code',
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect_uri' => 'https://example.com/callback'
        ]);
        $tokenRequest = $tokenRequest->withUri(new Uri('https://example.com/token'));

        $authRequest = new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            (new DateTimeImmutable())->add(new DateInterval('PT5M')),
            'openid profile email'
        );
        $authRequest->setNonce('test_nonce');

        $user = $this->createMock(UserInterface::class);
        $user->method('getIdentifier')->willReturn('user123');

        $accessToken = $this->createMock(AccessTokenInterface::class);
        $accessToken->method('getUser')->willReturn($user);
        $accessToken->method('getToken')->willReturn('access_token_123');

        $refreshToken = $this->createMock(RefreshTokenInterface::class);

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');
        $client->method('getSecret')->willReturn('test_secret');
        $client->method('getScopes')->willReturn(['openid', 'profile', 'email', 'name']);

        $oauthTokenResponse = $this->createMock(OAuthTokenResponse::class);
        $oauthTokenResponse->method('getAccessToken')->willReturn($accessToken);
        $oauthTokenResponse->method('getRefreshToken')->willReturn($refreshToken);

        // Mock the parent class behavior by setting up the expected method calls
        $authCode = $this->createMock(AuthCodeInterface::class);
        $authCode->method('getCode')->willReturn('test_auth_code');
        $authCode->method('getClient')->willReturn($client);
        $authCode->method('getUser')->willReturn($user);
        $authCode->method('getExpiresAt')->willReturn((new DateTimeImmutable())->add(new DateInterval('PT5M')));
        $authCode->method('getScopes')->willReturn(['openid', 'profile', 'email']);

        $this->authCodeManager->method('findAuthCode')
            ->with('test_auth_code')
            ->willReturn($authCode);

        $this->clientManager->method('findClientById')
            ->with('test_client')
            ->willReturn($client);
        $this->clientManager->method('validateClientCredentials')
            ->with('test_client', 'test_secret')
            ->willReturn(true);

        $this->accessTokenManager->method('generateAccessToken')
            ->willReturn($accessToken);

        $this->refreshTokenManager->method('generateRefreshToken')
            ->willReturn($refreshToken);

        $this->claimResolver->method('resolveClaims')
            ->with(['openid', 'profile', 'email'], $user)
            ->willReturn([
                'sub' => 'user123',
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]);

        $response = $this->grant->handleTokenRequest($tokenRequest, $authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $response);
        $this->assertSame($accessToken, $response->getAccessToken());
        $this->assertSame($refreshToken, $response->getRefreshToken());
        $this->assertNotNull($response->getIdToken());

        // Verify ID token contains expected claims
        $idToken = $response->getIdToken();
        $this->assertInstanceOf(Token::class, $idToken);

        $claims = $idToken->claims();
        $this->assertEquals('test_client', $claims->get('aud')[0]);
        $this->assertEquals('user123', $claims->get('sub'));
        $this->assertEquals('test_nonce', $claims->get('nonce'));
        $this->assertEquals('https://example.com', $claims->get('iss'));
    }

    public function testHandleTokenRequestWithoutOpenIdScopeDoesNotGenerateIdToken(): void
    {
        $tokenRequest = new ServerRequest('POST', 'https://example.com/token');
        $tokenRequest = $tokenRequest->withParsedBody([
            'grant_type' => 'authorization_code',
            'code' => 'test_auth_code',
            'client_id' => 'test_client',
            'client_secret' => 'test_secret',
            'redirect_uri' => 'https://example.com/callback'
        ]);

        $authRequest = new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            (new DateTimeImmutable())->add(new DateInterval('PT5M')),
            'profile email' // No openid scope
        );

        $user = $this->createMock(UserInterface::class);
        $accessToken = $this->createMock(AccessTokenInterface::class);
        $accessToken->method('getUser')->willReturn($user);
        $refreshToken = $this->createMock(RefreshTokenInterface::class);

        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getIdentifier')->willReturn('test_client');
        $client->method('getSecret')->willReturn('test_secret');

        // Mock parent token response
        $authCode = $this->createMock(AuthCodeInterface::class);
        $authCode->method('getCode')->willReturn('test_auth_code');
        $authCode->method('getClient')->willReturn($client);
        $authCode->method('getUser')->willReturn($user);
        $authCode->method('getExpiresAt')->willReturn((new DateTimeImmutable())->add(new DateInterval('PT5M')));
        $authCode->method('getScopes')->willReturn(['profile', 'email']);

        $this->authCodeManager->method('findAuthCode')->willReturn($authCode);
        $this->clientManager->method('findClientById')->willReturn($client);
        $this->clientManager->method('validateClientCredentials')
            ->with('test_client', 'test_secret')
            ->willReturn(true);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($accessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($refreshToken);

        $response = $this->grant->handleTokenRequest($tokenRequest, $authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $response);
        $this->assertNull($response->getIdToken());
    }

    public function testValidateScopesWithValidOAuthScopes(): void
    {
        $scopes = ['openid', 'profile', 'email'];
        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getScopes')->willReturn(['openid', 'profile', 'email', 'phone']);

        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => in_array($scope, ['openid', 'profile', 'email']));

        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')->willReturn(false);
        $this->claimRegistry->method('getGroups')->willReturn([]);

        $this->grant->validateScopes($scopes, $client);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testValidateScopesWithValidOidcClaims(): void
    {
        $scopes = ['openid', 'name', 'email'];
        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getScopes')->willReturn(['openid', 'name', 'email']);

        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => $scope === 'openid');

        $this->claimRegistry->method('isValidClaim')
            ->willReturnCallback(fn($claim) => in_array($claim, ['name', 'email']));

        $this->claimRegistry->method('isValidGroup')->willReturn(false);
        $this->claimRegistry->method('getGroups')->willReturn([]);

        $this->grant->validateScopes($scopes, $client);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testValidateScopesWithValidOidcGroups(): void
    {
        $scopes = ['openid', 'profile'];
        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getScopes')->willReturn(['openid']);

        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => $scope === 'openid');

        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => $group === 'profile');

        $this->claimRegistry->method('getGroups')->willReturn(['profile']);

        $this->grant->validateScopes($scopes, $client);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testValidateScopesThrowsExceptionForInvalidScope(): void
    {
        $scopes = ['openid', 'invalid_scope'];
        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getScopes')->willReturn(['openid']);

        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => $scope === 'openid');

        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')->willReturn(false);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid scope: invalid_scope');

        $this->grant->validateScopes($scopes, $client);
    }

    public function testValidateScopesThrowsExceptionForUnauthorizedScope(): void
    {
        $scopes = ['openid', 'profile'];
        $client = $this->createMock(ClientInterface::class);
        $client->method('isConfidential')->willReturn(true);
        $client->method('getScopes')->willReturn(['openid']); // Client doesn't have profile scope

        $this->scopeRegistry->method('isValidScope')
            ->willReturnCallback(fn($scope) => $scope === 'openid');

        $this->claimRegistry->method('isValidClaim')->willReturn(false);
        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => $group === 'profile');

        $this->claimRegistry->method('getGroups')->willReturn([]);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid scope "profile" for client.');

        $this->grant->validateScopes($scopes, $client);
    }

    public function testFlattenScopesExpandsGroups(): void
    {
        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => in_array($group, ['profile', 'address']));

        $this->claimRegistry->method('getGroup')
            ->willReturnMap([
                ['profile', ['name', 'given_name', 'family_name', 'picture']],
                ['address', ['address', 'locality', 'region', 'postal_code', 'country']]
            ]);

        $flattened = $this->grant->flattenScopes('openid profile address email');

        $expected = [
            'openid',
            'name',
            'given_name',
            'family_name',
            'picture',
            'address',
            'locality',
            'region',
            'postal_code',
            'country',
            'email'
        ];

        $this->assertEquals($expected, $flattened);
    }

    public function testFlattenScopesWithNoGroups(): void
    {
        $this->claimRegistry->method('isValidGroup')->willReturn(false);

        $flattened = $this->grant->flattenScopes('openid name email');

        $this->assertEquals(['openid', 'name', 'email'], $flattened);
    }

    public function testFlattenScopesRemovesDuplicates(): void
    {
        $this->claimRegistry->method('isValidGroup')
            ->willReturnCallback(fn($group) => $group === 'profile');

        $this->claimRegistry->method('getGroup')
            ->with('profile')
            ->willReturn(['name', 'email', 'picture']);

        $flattened = $this->grant->flattenScopes('openid profile email name');

        // Should remove duplicates
        $expected = ['openid', 'name', 'email', 'picture'];
        sort($expected);
        sort($flattened);

        $this->assertEquals($expected, $flattened);
    }

    public function testFlattenScopesWithEmptyInput(): void
    {
        $flattened = $this->grant->flattenScopes('');

        $this->assertEquals([''], $flattened);
    }

    public function testConstructorWithCustomTtls(): void
    {
        $codeTtl = new DateInterval('PT10M');
        $accessTokenTtl = new DateInterval('PT2H');
        $refreshTokenTtl = new DateInterval('P7D');

        $grant = new AuthorizationCodeGrant(
            $this->jwtConfig,
            $this->claimRegistry,
            $this->claimResolver,
            $this->authCodeManager,
            $this->accessTokenManager,
            $this->refreshTokenManager,
            $this->clientManager,
            $this->secureStringService,
            $this->scopeRegistry,
            $codeTtl,
            $accessTokenTtl,
            $refreshTokenTtl
        );

        $this->assertInstanceOf(AuthorizationCodeGrant::class, $grant);
    }
}
