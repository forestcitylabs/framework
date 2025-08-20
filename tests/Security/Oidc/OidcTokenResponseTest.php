<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc;

use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use ForestCityLabs\Framework\Security\Oidc\OidcTokenResponse;
use Lcobucci\JWT\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcTokenResponse::class)]
#[Group('oidc')]
#[Group('security')]
class OidcTokenResponseTest extends TestCase
{
    private AccessTokenInterface $accessToken;
    private RefreshTokenInterface $refreshToken;
    private Token $idToken;

    protected function setUp(): void
    {
        $this->accessToken = $this->createMock(AccessTokenInterface::class);
        $this->refreshToken = $this->createMock(RefreshTokenInterface::class);
        $this->idToken = $this->createMock(Token::class);

        // Configure default access token behavior
        $this->accessToken->method('getToken')->willReturn('access_token_12345');
        $this->accessToken->method('getScopes')->willReturn(['openid', 'profile', 'email']);
        $this->accessToken->method('getExpiresAt')->willReturn(new DateTimeImmutable('+1 hour'));

        // Configure default refresh token behavior
        $this->refreshToken->method('getToken')->willReturn('refresh_token_67890');

        // Configure default ID token behavior
        $this->idToken->method('toString')->willReturn('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjM0NSJ9.signature');
    }

    #[Test]
    public function constructorWithAccessTokenOnly()
    {
        $response = new OidcTokenResponse($this->accessToken);

        $this->assertInstanceOf(OAuthTokenResponse::class, $response);
        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
        $this->assertNull($response->getIdToken());
    }

    #[Test]
    public function constructorWithRefreshToken()
    {
        $response = new OidcTokenResponse($this->accessToken, $this->refreshToken);

        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertSame($this->refreshToken, $response->getRefreshToken());
        $this->assertNull($response->getIdToken());
    }

    #[Test]
    public function constructorWithIdToken()
    {
        $response = new OidcTokenResponse($this->accessToken, null, $this->idToken);

        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
        $this->assertSame($this->idToken, $response->getIdToken());
    }

    #[Test]
    public function constructorWithAllTokens()
    {
        $response = new OidcTokenResponse($this->accessToken, $this->refreshToken, $this->idToken);

        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertSame($this->refreshToken, $response->getRefreshToken());
        $this->assertSame($this->idToken, $response->getIdToken());
    }

    #[Test]
    public function getIdTokenReturnsNullWhenNotSet()
    {
        $response = new OidcTokenResponse($this->accessToken);

        $this->assertNull($response->getIdToken());
    }

    #[Test]
    public function getIdTokenReturnsTokenWhenSet()
    {
        $response = new OidcTokenResponse($this->accessToken, null, $this->idToken);

        $this->assertSame($this->idToken, $response->getIdToken());
    }

    #[Test]
    public function formatResponseWithoutIdToken()
    {
        // Mock the current time for consistent testing
        $currentTime = time();
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($this->accessToken, $this->refreshToken);
        $formattedResponse = $response->formatResponse();

        $expected = [
            'access_token' => 'access_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->getTimestamp() - time(),
            'refresh_token' => 'refresh_token_67890',
            'scope' => 'openid profile email',
        ];

        $this->assertEquals($expected['access_token'], $formattedResponse['access_token']);
        $this->assertEquals($expected['token_type'], $formattedResponse['token_type']);
        $this->assertEquals($expected['refresh_token'], $formattedResponse['refresh_token']);
        $this->assertEquals($expected['scope'], $formattedResponse['scope']);
        $this->assertIsInt($formattedResponse['expires_in']);
        $this->assertArrayNotHasKey('id_token', $formattedResponse);
    }

    #[Test]
    public function formatResponseWithIdToken()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($this->accessToken, $this->refreshToken, $this->idToken);
        $formattedResponse = $response->formatResponse();

        $expected = [
            'access_token' => 'access_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->getTimestamp() - time(),
            'refresh_token' => 'refresh_token_67890',
            'scope' => 'openid profile email',
            'id_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjM0NSJ9.signature',
        ];

        $this->assertEquals($expected['access_token'], $formattedResponse['access_token']);
        $this->assertEquals($expected['token_type'], $formattedResponse['token_type']);
        $this->assertEquals($expected['refresh_token'], $formattedResponse['refresh_token']);
        $this->assertEquals($expected['scope'], $formattedResponse['scope']);
        $this->assertEquals($expected['id_token'], $formattedResponse['id_token']);
        $this->assertIsInt($formattedResponse['expires_in']);
    }

    #[Test]
    public function formatResponseWithoutRefreshToken()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($this->accessToken, null, $this->idToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('access_token_12345', $formattedResponse['access_token']);
        $this->assertEquals('Bearer', $formattedResponse['token_type']);
        $this->assertEquals('openid profile email', $formattedResponse['scope']);
        $this->assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjM0NSJ9.signature', $formattedResponse['id_token']);
        $this->assertIsInt($formattedResponse['expires_in']);
        $this->assertNull($formattedResponse['refresh_token']);
    }

    #[Test]
    public function formatResponseIncludesParentFields()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($this->accessToken);
        $formattedResponse = $response->formatResponse();

        // Verify all parent class fields are present
        $this->assertArrayHasKey('access_token', $formattedResponse);
        $this->assertArrayHasKey('token_type', $formattedResponse);
        $this->assertArrayHasKey('expires_in', $formattedResponse);
        $this->assertArrayHasKey('refresh_token', $formattedResponse);
        $this->assertArrayHasKey('scope', $formattedResponse);
    }

    #[Test]
    public function inheritanceFromOAuthTokenResponse()
    {
        $response = new OidcTokenResponse($this->accessToken);

        $this->assertInstanceOf(OAuthTokenResponse::class, $response);
        
        // Test inherited methods work correctly
        $this->assertSame($this->accessToken, $response->getAccessToken());
        $this->assertNull($response->getRefreshToken());
    }

    #[Test]
    public function formatResponseCallsParentMethod()
    {
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $this->accessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($this->accessToken, $this->refreshToken);
        $formattedResponse = $response->formatResponse();

        // Verify the response contains the expected parent class structure
        $this->assertEquals('access_token_12345', $formattedResponse['access_token']);
        $this->assertEquals('Bearer', $formattedResponse['token_type']);
        $this->assertEquals('refresh_token_67890', $formattedResponse['refresh_token']);
        $this->assertEquals('openid profile email', $formattedResponse['scope']);
        $this->assertIsInt($formattedResponse['expires_in']);
    }

    #[Test]
    public function idTokenStringConversion()
    {
        $customJwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ1c2VyMTIzIiwibmFtZSI6IkpvaG4gRG9lIn0.signature';
        
        $customIdToken = $this->createMock(Token::class);
        $customIdToken->method('toString')->willReturn($customJwt);

        $response = new OidcTokenResponse($this->accessToken, null, $customIdToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals($customJwt, $formattedResponse['id_token']);
    }

    #[Test]
    public function emptyScopes()
    {
        $emptyAccessToken = $this->createMock(AccessTokenInterface::class);
        $emptyAccessToken->method('getToken')->willReturn('access_token_12345');
        $emptyAccessToken->method('getScopes')->willReturn([]);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $emptyAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($emptyAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('', $formattedResponse['scope']);
    }

    #[Test]
    public function singleScope()
    {
        $singleAccessToken = $this->createMock(AccessTokenInterface::class);
        $singleAccessToken->method('getToken')->willReturn('access_token_12345');
        $singleAccessToken->method('getScopes')->willReturn(['openid']);
        $expiresAt = new DateTimeImmutable('+3600 seconds');
        $singleAccessToken->method('getExpiresAt')->willReturn($expiresAt);

        $response = new OidcTokenResponse($singleAccessToken);
        $formattedResponse = $response->formatResponse();

        $this->assertEquals('openid', $formattedResponse['scope']);
    }
}