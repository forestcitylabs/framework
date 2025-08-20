<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\OAuth\Grant;

use DateInterval;
use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Exception\OAuthException;
use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Manager\ClientManagerInterface;
use ForestCityLabs\Framework\Security\Manager\RefreshTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\ClientInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\Grant\RefreshTokenGrant;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Utility\SecureStringService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

#[CoversClass(RefreshTokenGrant::class)]
#[Group('oauth')]
#[Group('grant')]
#[UsesClass(OAuthTokenResponse::class)]
class RefreshTokenGrantTest extends TestCase
{
    private RefreshTokenManagerInterface $refreshTokenManager;
    private AccessTokenManagerInterface $accessTokenManager;
    private SecureStringService $secureStringService;
    private OAuthScopeRegistry $scopeRegistry;
    private ClientManagerInterface $clientManager;
    private ServerRequestInterface $request;
    private RefreshTokenInterface $refreshToken;
    private ClientInterface $client;
    private UserInterface $user;
    private RefreshTokenGrant $grant;

    protected function setUp(): void
    {
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->accessTokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->secureStringService = $this->createMock(SecureStringService::class);
        $this->scopeRegistry = $this->createMock(OAuthScopeRegistry::class);
        $this->clientManager = $this->createMock(ClientManagerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->refreshToken = $this->createMock(RefreshTokenInterface::class);
        $this->client = $this->createMock(ClientInterface::class);
        $this->user = $this->createMock(UserInterface::class);

        $this->grant = new RefreshTokenGrant(
            $this->refreshTokenManager,
            $this->accessTokenManager,
            $this->secureStringService,
            $this->scopeRegistry,
            $this->clientManager
        );
    }

    #[Test]
    public function cannotHandleAuthorizationRequest()
    {
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getQueryParams')->willReturn([]);

        $result = $this->grant->canHandleAuthorizationRequest($this->request);

        $this->assertFalse($result);
    }

    #[Test]
    public function handleAuthorizationRequestThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refresh token grant does not handle authorization requests.');

        $this->grant->handleAuthorizationRequest($this->request);
    }

    #[Test]
    public function approveAuthorizationRequestThrowsException()
    {
        $authRequest = $this->createMock(AuthRequest::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refresh token grant does not approve authorization requests.');

        $this->grant->approveAuthorizationRequest($authRequest, $this->request);
    }

    #[Test]
    public function canHandleTokenRequestWithRefreshToken()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')->willReturn(['grant_type' => 'refresh_token']);

        $result = $this->grant->canHandleTokenRequest($this->request);

        $this->assertTrue($result);
    }

    #[Test]
    public function cannotHandleTokenRequestWithDifferentGrantType()
    {
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getParsedBody')->willReturn(['grant_type' => 'authorization_code']);

        $result = $this->grant->canHandleTokenRequest($this->request);

        $this->assertFalse($result);
    }

    #[Test]
    public function cannotHandleTokenRequestWithGetMethod()
    {
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getParsedBody')->willReturn(['grant_type' => 'refresh_token']);

        $result = $this->grant->canHandleTokenRequest($this->request);

        $this->assertFalse($result);
    }

    #[Test]
    public function handleTokenRequestThrowsExceptionForInvalidRefreshToken()
    {
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'invalid_token',
            'client_id' => 'client123',
        ]);

        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->with('invalid_token')
            ->willReturn(null);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Invalid refresh token.');

        $this->grant->handleTokenRequest($this->request, null);
    }

    #[Test]
    public function handleTokenRequestThrowsExceptionForInvalidClient()
    {
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'invalid_client',
        ]);

        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->with('valid_token')
            ->willReturn($this->refreshToken);

        $this->clientManager
            ->method('findClientById')
            ->with('invalid_client')
            ->willReturn(null);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Client not found for the refresh token request.');

        $this->grant->handleTokenRequest($this->request, null);
    }

    #[Test]
    public function handleTokenRequestThrowsExceptionForClientMismatch()
    {
        $differentClient = $this->createMock(ClientInterface::class);

        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->with('valid_token')
            ->willReturn($this->refreshToken);

        $this->clientManager
            ->method('findClientById')
            ->with('client123')
            ->willReturn($this->client);

        $this->refreshToken
            ->method('getClient')
            ->willReturn($differentClient);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Client mismatch for the refresh token.');

        $this->grant->handleTokenRequest($this->request, null);
    }

    #[Test]
    public function handleTokenRequestThrowsExceptionForExpiredRefreshToken()
    {
        $expiredDate = new DateTimeImmutable('-1 day');

        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'expired_token',
            'client_id' => 'client123',
        ]);

        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->with('expired_token')
            ->willReturn($this->refreshToken);

        $this->clientManager
            ->method('findClientById')
            ->with('client123')
            ->willReturn($this->client);

        $this->refreshToken
            ->method('getClient')
            ->willReturn($this->client);

        $this->refreshToken
            ->method('getExpiresAt')
            ->willReturn($expiredDate);

        $this->refreshTokenManager
            ->expects($this->once())
            ->method('revokeRefreshToken')
            ->with($this->refreshToken);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Refresh token has expired.');

        $this->grant->handleTokenRequest($this->request, null);
    }

    #[Test]
    public function handleTokenRequestSuccessfullyRefreshesToken()
    {
        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        // Mock refresh token lookup
        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->with('valid_token')
            ->willReturn($this->refreshToken);

        // Mock client lookup
        $this->clientManager
            ->method('findClientById')
            ->with('client123')
            ->willReturn($this->client);

        // Mock refresh token properties
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['read', 'write']);

        // Mock scope filtering
        $this->scopeRegistry
            ->method('filterPrivilegedScopes')
            ->with(['read', 'write'])
            ->willReturn(['read', 'write']);

        // Mock access token generation
        $this->accessTokenManager
            ->method('generateAccessToken')
            ->willReturn($newAccessToken);

        $this->secureStringService
            ->method('generateRandomString')
            ->with(128)
            ->willReturnOnConsecutiveCalls('new_access_token', 'new_refresh_token');

        // Mock access token configuration
        $newAccessToken->expects($this->once())->method('setUser')->with($this->user);
        $newAccessToken->expects($this->once())->method('setToken')->with('new_access_token');
        $newAccessToken->expects($this->once())->method('setExpiresAt')->with($this->isInstanceOf(DateTimeImmutable::class));
        $newAccessToken->expects($this->exactly(2))->method('addScope')->with($this->logicalOr('read', 'write'));
        $newAccessToken->method('getScopes')->willReturn(['read', 'write']);

        // Mock access token persistence
        $this->accessTokenManager
            ->expects($this->once())
            ->method('persistAccessToken')
            ->with($newAccessToken);

        // Mock refresh token generation
        $this->refreshTokenManager
            ->method('generateRefreshToken')
            ->willReturn($newRefreshToken);

        // Mock refresh token configuration
        $newRefreshToken->expects($this->once())->method('setUser')->with($this->user);
        $newRefreshToken->expects($this->once())->method('setToken')->with('new_refresh_token');
        $newRefreshToken->expects($this->once())->method('setExpiresAt')->with($this->isInstanceOf(DateTimeImmutable::class));
        $newRefreshToken->expects($this->once())->method('setClient')->with($this->client);
        $newRefreshToken->expects($this->exactly(2))->method('addScope')->with($this->logicalOr('read', 'write'));

        // Mock refresh token persistence
        $this->refreshTokenManager
            ->expects($this->once())
            ->method('persistRefreshToken')
            ->with($newRefreshToken);

        // Mock old refresh token revocation
        $this->refreshTokenManager
            ->expects($this->once())
            ->method('revokeRefreshToken')
            ->with($this->refreshToken);

        $result = $this->grant->handleTokenRequest($this->request, null);

        $this->assertInstanceOf(OAuthTokenResponse::class, $result);
        $this->assertSame($newAccessToken, $result->getAccessToken());
        $this->assertSame($newRefreshToken, $result->getRefreshToken());
    }

    #[Test]
    public function handleTokenRequestFiltersPrivilegedScopes()
    {
        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->willReturn($this->refreshToken);

        $this->clientManager
            ->method('findClientById')
            ->willReturn($this->client);

        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['read', 'write', 'admin']);

        // Mock scope filtering - admin scope is privileged and filtered out
        $this->scopeRegistry
            ->expects($this->once())
            ->method('filterPrivilegedScopes')
            ->with(['read', 'write', 'admin'])
            ->willReturn(['read', 'write']);

        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getScopes')->willReturn(['read', 'write']);

        // Verify only non-privileged scopes are added
        $newAccessToken->expects($this->exactly(2))->method('addScope')->with($this->logicalOr('read', 'write'));
        $newRefreshToken->expects($this->exactly(2))->method('addScope')->with($this->logicalOr('read', 'write'));

        $this->grant->handleTokenRequest($this->request, null);
    }

    #[Test]
    public function constructorUsesCustomTtlValues()
    {
        $customAccessTtl = new DateInterval('PT2H');
        $customRefreshTtl = new DateInterval('P2M');

        $customGrant = new RefreshTokenGrant(
            $this->refreshTokenManager,
            $this->accessTokenManager,
            $this->secureStringService,
            $this->scopeRegistry,
            $this->clientManager,
            $customAccessTtl,
            $customRefreshTtl
        );

        // This test verifies construction succeeds with custom TTL values
        $this->assertInstanceOf(RefreshTokenGrant::class, $customGrant);
    }

    #[Test]
    public function handleTokenRequestUsesCorrectTtlValues()
    {
        $customAccessTtl = new DateInterval('PT2H');
        $customRefreshTtl = new DateInterval('P2M');

        $customGrant = new RefreshTokenGrant(
            $this->refreshTokenManager,
            $this->accessTokenManager,
            $this->secureStringService,
            $this->scopeRegistry,
            $this->clientManager,
            $customAccessTtl,
            $customRefreshTtl
        );

        $futureDate = new DateTimeImmutable('+1 month');
        $newAccessToken = $this->createMock(AccessTokenInterface::class);
        $newRefreshToken = $this->createMock(RefreshTokenInterface::class);

        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            'client_id' => 'client123',
        ]);

        $this->refreshTokenManager->method('findRefreshTokenByToken')->willReturn($this->refreshToken);
        $this->clientManager->method('findClientById')->willReturn($this->client);
        $this->refreshToken->method('getClient')->willReturn($this->client);
        $this->refreshToken->method('getExpiresAt')->willReturn($futureDate);
        $this->refreshToken->method('getUser')->willReturn($this->user);
        $this->refreshToken->method('getScopes')->willReturn(['read']);

        $this->scopeRegistry->method('filterPrivilegedScopes')->willReturn(['read']);
        $this->accessTokenManager->method('generateAccessToken')->willReturn($newAccessToken);
        $this->refreshTokenManager->method('generateRefreshToken')->willReturn($newRefreshToken);
        $this->secureStringService->method('generateRandomString')->willReturn('random_string');

        $newAccessToken->method('getScopes')->willReturn(['read']);

        // Verify TTL values are used correctly (within reasonable time bounds)
        $newAccessToken->expects($this->once())
            ->method('setExpiresAt')
            ->with($this->callback(function (DateTimeImmutable $expiresAt) {
                $now = new DateTimeImmutable();
                $expectedMin = $now->add(new DateInterval('PT1H50M')); // ~2 hours - 10 minutes
                $expectedMax = $now->add(new DateInterval('PT2H10M')); // ~2 hours + 10 minutes
                return $expiresAt >= $expectedMin && $expiresAt <= $expectedMax;
            }));

        $newRefreshToken->expects($this->once())
            ->method('setExpiresAt')
            ->with($this->callback(function (DateTimeImmutable $expiresAt) {
                $now = new DateTimeImmutable();
                $expectedMin = $now->add(new DateInterval('P1M29D')); // ~2 months - 1 day
                $expectedMax = $now->add(new DateInterval('P2M1D'));  // ~2 months + 1 day
                return $expiresAt >= $expectedMin && $expiresAt <= $expectedMax;
            }));

        $customGrant->handleTokenRequest($this->request, null);
    }

    #[Test]
    public function handleTokenRequestWithMissingClientId()
    {
        $this->request->method('getParsedBody')->willReturn([
            'refresh_token' => 'valid_token',
            // client_id is missing
        ]);

        $this->refreshTokenManager
            ->method('findRefreshTokenByToken')
            ->willReturn($this->refreshToken);

        $this->clientManager
            ->method('findClientById')
            ->with('') // Should try to find client with empty string
            ->willReturn(null);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage('Client not found for the refresh token request.');

        $this->grant->handleTokenRequest($this->request, null);
    }
}

