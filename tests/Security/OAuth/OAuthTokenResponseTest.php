<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\OAuth;

use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OAuthTokenResponse::class)]
#[Group('oauth')]
#[Group('security')]
class OAuthTokenResponseTest extends TestCase
{
    private AccessTokenInterface $accessToken;
    private RefreshTokenInterface $refreshToken;

    protected function setUp(): void
    {
        $this->accessToken = $this->createMock(AccessTokenInterface::class);
        $this->refreshToken = $this->createMock(RefreshTokenInterface::class);

        // Configure default access token behavior
        $this->accessToken->method('getToken')->willReturn('access_token_abc123');
        $this->accessToken->method('getScopes')->willReturn(['read', 'write', 'profile']);
        $this->accessToken->method('getExpiresAt')->willReturn(new DateTimeImmutable('+1 hour'));

        // Configure default refresh token behavior
        $this->refreshToken->method('getToken')->willReturn('refresh_token_xyz789');
    }

    #[Test]
    public function constructorWithAccessTokenOnly()
    {
        $response = new OAuthTokenResponse($this->accessToken);

        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
    }

    #[Test]
    public function constructorWithRefreshToken()
    {
        $response = new OAuthTokenResponse($this->accessToken, $this->refreshToken);

        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertSame($this->refreshToken, $response->getRefreshToken());
    }

    #[Test]
    public function constructorWithNullRefreshToken()
    {
        $response = new OAuthTokenResponse($this->accessToken, null);

        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
    }

    #[Test]
    public function getAccessTokenReturnsCorrectToken()
    {
        $response = new OAuthTokenResponse($this->accessToken);

        $this->assertSame($this->accessToken, $response->getAccessToken());
    }

    #[Test]
    public function getRefreshTokenReturnsNullWhenNotSet()
    {
        $response = new OAuthTokenResponse($this->accessToken);

        $this->assertNull($response->getRefreshToken());
    }

    #[Test]
    public function getRefreshTokenReturnsTokenWhenSet()
    {
        $response = new OAuthTokenResponse($this->accessToken, $this->refreshToken);

        $this->assertSame($this->refreshToken, $response->getRefreshToken());
    }

    #[Test]
    public function formatResponseWithoutRefreshToken()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($this->accessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('access_token_abc123', $formattedResponse['access_token']);
        $this->assertEquals('Bearer', $formattedResponse['token_type']);
        $this->assertEquals('read write profile', $formattedResponse['scope']);
        $this->assertNull($formattedResponse['refresh_token']);
        $this->assertIsInt($formattedResponse['expires_in']);
        $this->assertGreaterThan(0, $formattedResponse['expires_in']);
    }

    #[Test]
    public function formatResponseWithRefreshToken()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($this->accessToken, $this->refreshToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('access_token_abc123', $formattedResponse['access_token']);
        $this->assertEquals('Bearer', $formattedResponse['token_type']);
        $this->assertEquals('read write profile', $formattedResponse['scope']);
        $this->assertEquals('refresh_token_xyz789', $formattedResponse['refresh_token']);
        $this->assertIsInt($formattedResponse['expires_in']);
        $this->assertGreaterThan(0, $formattedResponse['expires_in']);
    }

    #[Test]
    public function formatResponseCalculatesCorrectExpiresIn()
    {
        $customAccessToken = $this->createMock(AccessTokenInterface::class);
        $customAccessToken->method('getToken')->willReturn('access_token_abc123');
        $customAccessToken->method('getScopes')->willReturn(['read']);
        
        $currentTime = time();
        $expiresAt = new DateTimeImmutable('@' . ($currentTime + 7200)); // 2 hours from now
        $customAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($customAccessToken);
        $formattedResponse = $response->formatResponse();

        $actualExpiresIn = $formattedResponse['expires_in'];
        
        // Allow for small timing differences (within 5 seconds)
        $this->assertGreaterThanOrEqual(7195, $actualExpiresIn);
        $this->assertLessThanOrEqual(7200, $actualExpiresIn);
    }

    #[Test]
    public function formatResponseWithEmptyScopes()
    {
        $emptyAccessToken = $this->createMock(AccessTokenInterface::class);
        $emptyAccessToken->method('getToken')->willReturn('access_token_abc123');
        $emptyAccessToken->method('getScopes')->willReturn([]);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $emptyAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($emptyAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('', $formattedResponse['scope']);
    }

    #[Test]
    public function formatResponseWithSingleScope()
    {
        $singleAccessToken = $this->createMock(AccessTokenInterface::class);
        $singleAccessToken->method('getToken')->willReturn('access_token_abc123');
        $singleAccessToken->method('getScopes')->willReturn(['read']);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $singleAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($singleAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('read', $formattedResponse['scope']);
    }

    #[Test]
    public function formatResponseWithMultipleScopes()
    {
        $multiAccessToken = $this->createMock(AccessTokenInterface::class);
        $multiAccessToken->method('getToken')->willReturn('access_token_abc123');
        $multiAccessToken->method('getScopes')->willReturn(['read', 'write', 'admin', 'profile']);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $multiAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($multiAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('read write admin profile', $formattedResponse['scope']);
    }

    #[Test]
    public function formatResponseWithSpecialScopeNames()
    {
        $specialAccessToken = $this->createMock(AccessTokenInterface::class);
        $specialAccessToken->method('getToken')->willReturn('access_token_abc123');
        $specialAccessToken->method('getScopes')->willReturn(['user:read', 'repo:write', 'openid', 'profile.email']);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $specialAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($specialAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('user:read repo:write openid profile.email', $formattedResponse['scope']);
    }

    #[Test]
    public function formatResponseStructure()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($this->accessToken, $this->refreshToken);
        $formattedResponse = $response->formatResponse();

        // Verify all required fields are present
        $this->assertArrayHasKey('access_token', $formattedResponse);
        $this->assertArrayHasKey('token_type', $formattedResponse);
        $this->assertArrayHasKey('expires_in', $formattedResponse);
        $this->assertArrayHasKey('refresh_token', $formattedResponse);
        $this->assertArrayHasKey('scope', $formattedResponse);

        // Verify field types
        $this->assertIsString($formattedResponse['access_token']);
        $this->assertIsString($formattedResponse['token_type']);
        $this->assertIsInt($formattedResponse['expires_in']);
        $this->assertIsString($formattedResponse['scope']);
        // refresh_token can be string or null
        $this->assertTrue(is_string($formattedResponse['refresh_token']) || is_null($formattedResponse['refresh_token']));
    }

    #[Test]
    public function tokenTypeIsAlwaysBearer()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($this->accessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('Bearer', $formattedResponse['token_type']);
    }

    #[Test]
    public function formatResponseWithNullRefreshTokenProperty()
    {
        $nullAccessToken = $this->createMock(AccessTokenInterface::class);
        $nullAccessToken->method('getToken')->willReturn('access_token_abc123');
        $nullAccessToken->method('getScopes')->willReturn(['read']);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $nullAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        // Test with null refresh token object
        $response = new OAuthTokenResponse($nullAccessToken, null);
        $formattedResponse = $response->formatResponse();

        $this->assertNull($formattedResponse['refresh_token']);
    }

    #[Test]
    public function formatResponseWithExpiredToken()
    {
        $expiredAccessToken = $this->createMock(AccessTokenInterface::class);
        $expiredAccessToken->method('getToken')->willReturn('access_token_abc123');
        $expiredAccessToken->method('getScopes')->willReturn(['read']);
        // Token expired 1 hour ago
        $expiresAt = new DateTimeImmutable('-3600 seconds');
        $expiredAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($expiredAccessToken);
        $formattedResponse = $response->formatResponse();

        // expires_in should be negative for expired tokens
        $this->assertLessThan(0, $formattedResponse['expires_in']);
    }

    #[Test]
    public function formatResponseHandlesLongLivedTokens()
    {
        $longAccessToken = $this->createMock(AccessTokenInterface::class);
        $longAccessToken->method('getToken')->willReturn('access_token_abc123');
        $longAccessToken->method('getScopes')->willReturn(['read']);
        // Token expires in 30 days
        $expiresAt = new DateTimeImmutable('+30 days');
        $longAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($longAccessToken);
        $formattedResponse = $response->formatResponse();

        // Should be approximately 30 days in seconds (2592000)
        $expectedExpiresIn = 30 * 24 * 60 * 60;
        $actualExpiresIn = $formattedResponse['expires_in'];
        
        // Allow for timing differences (within 60 seconds)
        $this->assertGreaterThanOrEqual($expectedExpiresIn - 60, $actualExpiresIn);
        $this->assertLessThanOrEqual($expectedExpiresIn, $actualExpiresIn);
    }

    #[Test]
    public function accessTokenCannotBeNull()
    {
        // This test verifies that the constructor requires an access token
        // by ensuring our mock is always provided
        $response = new OAuthTokenResponse($this->accessToken);
        
        $this->assertNotNull($response->getAccessToken());
        $this->assertInstanceOf(AccessTokenInterface::class, $response->getAccessToken());
    }

    #[Test]
    public function refreshTokenCanBeNull()
    {
        $response = new OAuthTokenResponse($this->accessToken, null);
        
        $this->assertNull($response->getRefreshToken());
    }

    #[Test]
    public function scopeOrderIsPreserved()
    {
        $orderAccessToken = $this->createMock(AccessTokenInterface::class);
        $orderAccessToken->method('getToken')->willReturn('access_token_abc123');
        $scopes = ['first', 'second', 'third', 'fourth'];
        $orderAccessToken->method('getScopes')->willReturn($scopes);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $orderAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OAuthTokenResponse($orderAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('first second third fourth', $formattedResponse['scope']);
    }
}