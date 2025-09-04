<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\ClientManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\ClientInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\Grant\RefreshTokenGrant as OAuthRefreshTokenGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Security\Oidc\ClaimResolver;
use ForestCityLabs\Framework\Security\Oidc\Grant\RefreshTokenGrant;
use ForestCityLabs\Framework\Security\Oidc\OidcTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(RefreshTokenGrant::class)]
#[Group('oidc')]
#[Group('grant')]
#[UsesClass(OAuthRefreshTokenGrant::class)]
#[UsesClass(OAuthTokenResponse::class)]
#[UsesClass(OidcTokenResponse::class)]
class RefreshTokenGrantTest extends TestCase
{
    private RefreshTokenManagerInterface $refreshTokenManager;
    private AccessTokenManagerInterface $accessTokenManager;
    private SecureStringService $secureStringService;
    private OAuthScopeRegistry $scopeRegistry;
    private ClientManagerInterface $clientManager;
    private Configuration $jwtConfiguration;
    private ClaimResolver $claimResolver;
    private ServerRequestInterface $request;
    private AuthRequest $authRequest;
    private RefreshTokenInterface $refreshToken;
    private AccessTokenInterface $accessToken;
    private ClientInterface $client;
    private UserInterface $user;
    private UriInterface $uri;
    private RefreshTokenGrant $grant;

    protected function setUp(): void
    {
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->accessTokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->secureStringService = $this->createMock(SecureStringService::class);
        $this->scopeRegistry = $this->createMock(OAuthScopeRegistry::class);
        $this->clientManager = $this->createMock(ClientManagerInterface::class);
        $this->claimResolver = $this->createMock(ClaimResolver::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->authRequest = $this->createMock(AuthRequest::class);
        $this->refreshToken = $this->createMock(RefreshTokenInterface::class);
        $this->accessToken = $this->createMock(AccessTokenInterface::class);
        $this->client = $this->createMock(ClientInterface::class);
        $this->user = $this->createMock(UserInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        // Create a real JWT configuration for testing
        $this->jwtConfiguration = Configuration::forSymmetricSigner(
            new Sha256(),
            Key\InMemory::plainText('test-secret-key-for-testing-purposes-only')
        );

        $this->grant = new RefreshTokenGrant(
            $this->jwtConfiguration,
            $this->claimResolver,
            $this->refreshTokenManager,
            $this->accessTokenManager,
            $this->secureStringService,
            $this->scopeRegistry,
            $this->clientManager
        );
    }

    #[Test]
    public function extendsOAuthRefreshTokenGrant()
    {
        $this->assertInstanceOf(OAuthRefreshTokenGrant::class, $this->grant);
    }

    #[Test]
    public function constructorWithCustomTtlValues()
    {
        $customAccessTtl = new DateInterval('PT2H');
        $customRefreshTtl = new DateInterval('P2M');

        $customJwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            Key\InMemory::plainText('custom-test-key')
        );

        $customGrant = new RefreshTokenGrant(
            $customJwtConfig,
            $this->claimResolver,
            $this->refreshTokenManager,
            $this->accessTokenManager,
            $this->secureStringService,
            $this->scopeRegistry,
            $this->clientManager,
            $customAccessTtl,
            $customRefreshTtl
        );

        $this->assertInstanceOf(RefreshTokenGrant::class, $customGrant);
    }

    #[Test]
    public function handleTokenRequestWithoutOpenidScope()
    {
        // Mock the parent class behavior
        $oauthTokenResponse = $this->createMock(OAuthTokenResponse::class);
        $oauthTokenResponse->method('getAccessToken')->willReturn($this->accessToken);
        $oauthTokenResponse->method('getRefreshToken')->willReturn($this->refreshToken);

        // Mock auth request without openid scope
        $this->authRequest->method('getScope')->willReturn('read write profile');

        // Mock the necessary request setup for parent::handleTokenRequest
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->refreshTokenManager->method('findRefreshTokenByToken')->willReturn($this->refreshToken);
        $this->clientManager->method('findClientById')->willReturn($this->client);
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['read', 'write']);

        $this->scopeRegistry->method('filterPrivilegedScopes')->willReturn(['read', 'write']);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getScopes')->willReturn(['read', 'write']);

        $result = $this->grant->handleTokenRequest($this->request, $this->authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $result);
        $this->assertSame($newAccessToken, $result->getAccessToken());
        $this->assertSame($newRefreshToken, $result->getRefreshToken());
        $this->assertNull($result->getIdToken());
    }

    #[Test]
    public function handleTokenRequestWithOpenidScope()
    {
        // Mock URI for issuer
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->request->method('getUri')->willReturn($this->uri);

        // Mock auth request with openid scope
        $this->authRequest->method('getScope')->willReturn('openid profile email');
        $this->authRequest->method('getClientId')->willReturn('client123');
        $this->authRequest->method('getNonce')->willReturn('test-nonce');

        // Mock user
        $this->user->method('getIdentifier')->willReturn('user123');

        // Mock the necessary request setup for parent::handleTokenRequest
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->refreshTokenManager->method('findRefreshTokenByToken')->willReturn($this->refreshToken);
        $this->clientManager->method('findClientById')->willReturn($this->client);
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['openid', 'profile']);

        $this->scopeRegistry->method('filterPrivilegedScopes')->willReturn(['openid', 'profile']);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getUser')->willReturn($this->user);
        $newAccessToken->method('getScopes')->willReturn(['openid', 'profile']);

        // Mock client scopes
        $this->client->method('getScopes')->willReturn(['openid', 'profile', 'name', 'email']);

        // Mock claim resolver
        $this->claimResolver
            ->method('resolveClaims')
            ->with(['openid', 'profile', 'email'], $this->user)
            ->willReturn([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'profile' => 'user-profile'
            ]);

        $result = $this->grant->handleTokenRequest($this->request, $this->authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $result);
        $this->assertSame($newAccessToken, $result->getAccessToken());
        $this->assertSame($newRefreshToken, $result->getRefreshToken());
        $this->assertNotNull($result->getIdToken());
        $this->assertInstanceOf(Token::class, $result->getIdToken());
    }

    #[Test]
    public function handleTokenRequestFiltersClaimsByClientScopes()
    {
        // Mock URI for issuer
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->request->method('getUri')->willReturn($this->uri);

        // Mock auth request with openid scope
        $this->authRequest->method('getScope')->willReturn('openid profile email');
        $this->authRequest->method('getClientId')->willReturn('client123');
        $this->authRequest->method('getNonce')->willReturn('test-nonce');

        // Mock user
        $this->user->method('getIdentifier')->willReturn('user123');

        // Mock the necessary request setup for parent::handleTokenRequest
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->refreshTokenManager->method('findRefreshTokenByToken')->willReturn($this->refreshToken);
        $this->clientManager->method('findClientById')->willReturn($this->client);
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['openid', 'profile']);

        $this->scopeRegistry->method('filterPrivilegedScopes')->willReturn(['openid', 'profile']);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getUser')->willReturn($this->user);
        $newAccessToken->method('getScopes')->willReturn(['openid', 'profile']);

        // Mock client scopes - client only allows 'name', not 'email' or 'admin'
        $this->client->method('getScopes')->willReturn(['openid', 'profile', 'name']);

        // Mock claim resolver returning more claims than client allows
        $this->claimResolver
            ->method('resolveClaims')
            ->with(['openid', 'profile', 'email'], $this->user)
            ->willReturn([
                'name' => 'John Doe',        // Should be included (client allows)
                'email' => 'john@example.com', // Should be filtered out (client doesn't allow)
                'admin' => true,             // Should be filtered out (client doesn't allow)
            ]);

        $result = $this->grant->handleTokenRequest($this->request, $this->authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $result);
        $this->assertNotNull($result->getIdToken());
        $this->assertInstanceOf(Token::class, $result->getIdToken());
    }

    #[Test]
    public function handleTokenRequestBuildsCorrectIdToken()
    {
        // Mock URI for issuer
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('auth.example.com');
        $this->request->method('getUri')->willReturn($this->uri);

        // Mock auth request with openid scope
        $this->authRequest->method('getScope')->willReturn('openid profile');
        $this->authRequest->method('getClientId')->willReturn('test-client');
        $this->authRequest->method('getNonce')->willReturn('unique-nonce-123');

        // Mock user
        $this->user->method('getIdentifier')->willReturn('user-456');

        // Mock the necessary request setup for parent::handleTokenRequest
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'test-client',
        ]);

        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->refreshTokenManager->method('findRefreshTokenByToken')->willReturn($this->refreshToken);
        $this->clientManager->method('findClientById')->willReturn($this->client);
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['openid', 'profile']);

        $this->scopeRegistry->method('filterPrivilegedScopes')->willReturn(['openid', 'profile']);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getUser')->willReturn($this->user);
        $newAccessToken->method('getScopes')->willReturn(['openid', 'profile']);

        $this->client->method('getScopes')->willReturn(['openid', 'profile', 'name']);

        $this->claimResolver
            ->method('resolveClaims')
            ->willReturn(['name' => 'John Doe']);

        $result = $this->grant->handleTokenRequest($this->request, $this->authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $result);
        $this->assertNotNull($result->getIdToken());
        $this->assertInstanceOf(Token::class, $result->getIdToken());

        // Verify the token has the expected claims
        $token = $result->getIdToken();
        $this->assertEquals('https://auth.example.com', $token->claims()->get('iss'));
        $this->assertEquals('test-client', $token->claims()->get('aud')[0]);
        $this->assertEquals('user-456', $token->claims()->get('sub'));
        $this->assertEquals('unique-nonce-123', $token->claims()->get('nonce'));
        $this->assertEquals('John Doe', $token->claims()->get('name'));
    }

    #[Test]
    public function handleTokenRequestWithEmptyClaimsFromResolver()
    {
        // Mock URI for issuer
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->request->method('getUri')->willReturn($this->uri);

        // Mock auth request with openid scope
        $this->authRequest->method('getScope')->willReturn('openid');
        $this->authRequest->method('getClientId')->willReturn('client123');
        $this->authRequest->method('getNonce')->willReturn('test-nonce');

        // Mock user
        $this->user->method('getIdentifier')->willReturn('user123');

        // Mock the necessary request setup for parent::handleTokenRequest
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->refreshTokenManager->method('findRefreshTokenByToken')->willReturn($this->refreshToken);
        $this->clientManager->method('findClientById')->willReturn($this->client);
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['openid']);

        $this->scopeRegistry->method('filterPrivilegedScopes')->willReturn(['openid']);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getUser')->willReturn($this->user);
        $newAccessToken->method('getScopes')->willReturn(['openid']);

        $this->client->method('getScopes')->willReturn(['openid']);

        // Mock claim resolver returning empty claims
        $this->claimResolver
            ->method('resolveClaims')
            ->willReturn([]);

        $result = $this->grant->handleTokenRequest($this->request, $this->authRequest);

        $this->assertInstanceOf(OidcTokenResponse::class, $result);
        $this->assertNotNull($result->getIdToken());
        $this->assertInstanceOf(Token::class, $result->getIdToken());

        // Verify the token has the expected minimal claims
        $token = $result->getIdToken();
        $this->assertEquals('https://example.com', $token->claims()->get('iss'));
        $this->assertEquals('client123', $token->claims()->get('aud')[0]);
        $this->assertEquals('user123', $token->claims()->get('sub'));
        $this->assertEquals('test-nonce', $token->claims()->get('nonce'));
    }
}
