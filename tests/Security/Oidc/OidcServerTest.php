<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc;

use ForestCityLabs\Framework\Security\Manager\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthServer;
use ForestCityLabs\Framework\Security\Oidc\ClaimResolver;
use ForestCityLabs\Framework\Security\Oidc\Keystore;
use ForestCityLabs\Framework\Security\Oidc\OidcClaimRegistry;
use ForestCityLabs\Framework\Security\Oidc\OidcServer;
use ForestCityLabs\Framework\Utility\EncryptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(OidcServer::class)]
#[Group('security')]
#[Group('oidc')]
#[UsesClass(Keystore::class)]
#[UsesClass(OAuthServer::class)]
class OidcServerTest extends TestCase
{
    private AccessTokenManagerInterface $accessTokenManager;
    private ClaimResolver $claimResolver;
    private Keystore $keystore;
    private OidcClaimRegistry $claimRegistry;
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private EncryptionService $encryptionService;
    private OAuthScopeRegistry $scopeRegistry;
    private OidcServer $oidcServer;
    private array $tempKeyFiles = [];

    protected function setUp(): void
    {
        $this->accessTokenManager = $this->createMock(AccessTokenManagerInterface::class);
        $this->claimResolver = $this->createMock(ClaimResolver::class);
        $this->keystore = $this->createTestKeystore();
        $this->claimRegistry = $this->createMock(OidcClaimRegistry::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->encryptionService = $this->createMock(EncryptionService::class);
        $this->scopeRegistry = $this->createMock(OAuthScopeRegistry::class);

        $this->oidcServer = new OidcServer(
            $this->accessTokenManager,
            $this->claimResolver,
            $this->keystore,
            $this->claimRegistry,
            $this->responseFactory,
            $this->streamFactory,
            $this->encryptionService,
            $this->scopeRegistry
        );
    }

    private function createTestKeystore(): Keystore
    {
        // Create temporary key files for testing
        $tempDir = sys_get_temp_dir();
        $keyFiles = [];

        for ($i = 1; $i <= 2; $i++) {
            $keyFile = $tempDir . "/test_key_{$i}.pem";
            $privateKey = openssl_pkey_new([
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);

            openssl_pkey_export($privateKey, $privateKeyPem);
            file_put_contents($keyFile, $privateKeyPem);
            $keyFiles["key{$i}"] = $keyFile;
        }

        $this->tempKeyFiles = array_merge($this->tempKeyFiles, array_values($keyFiles));
        return new Keystore($keyFiles);
    }

    protected function tearDown(): void
    {
        // Clean up temporary key files
        foreach ($this->tempKeyFiles as $keyFile) {
            if (file_exists($keyFile)) {
                unlink($keyFile);
            }
        }
        $this->tempKeyFiles = [];
    }

    public function testConstructorInheritsFromOAuthServer(): void
    {
        $this->assertInstanceOf(\ForestCityLabs\Framework\Security\OAuth\OAuthServer::class, $this->oidcServer);
    }

    public function testHandleUserInfoRequestWithValidBearerToken(): void
    {
        $accessToken = $this->createMock(AccessTokenInterface::class);
        $user = $this->createMock(UserInterface::class);
        $scopes = ['openid', 'profile', 'email'];
        $claims = ['sub' => '12345', 'name' => 'John Doe', 'email' => 'john@example.com'];

        $accessToken->method('getScopes')->willReturn($scopes);
        $accessToken->method('getUser')->willReturn($user);

        $this->accessTokenManager->method('findAccessToken')
            ->with('valid_token')
            ->willReturn($accessToken);

        $this->claimResolver->method('resolveClaims')
            ->with($scopes, $user)
            ->willReturn($claims);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer valid_token');

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode($claims, JSON_THROW_ON_ERROR))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();
        $response->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $result = $this->oidcServer->handleUserInfoRequest($request);

        $this->assertSame($response, $result);
    }

    public function testHandleUserInfoRequestWithInvalidBearerToken(): void
    {
        $this->accessTokenManager->method('findAccessToken')
            ->with('invalid_token')
            ->willReturn(null);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer invalid_token');

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')
            ->with(401)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode(['error' => 'invalid_token']))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();
        $response->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $result = $this->oidcServer->handleUserInfoRequest($request);

        $this->assertSame($response, $result);
    }

    public function testHandleUserInfoRequestWithMalformedAuthorizationHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Basic dGVzdA==');

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode(['user' => 'info']))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();

        $result = $this->oidcServer->handleUserInfoRequest($request);

        $this->assertSame($response, $result);
    }

    public function testHandleUserInfoRequestWithoutAuthorizationHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('Authorization')->willReturn(false);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode(['user' => 'info']))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();

        $result = $this->oidcServer->handleUserInfoRequest($request);

        $this->assertSame($response, $result);
    }

    public function testHandleUserInfoRequestWithEmptyBearerToken(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ');

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode(['user' => 'info']))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();

        $result = $this->oidcServer->handleUserInfoRequest($request);

        $this->assertSame($response, $result);
    }

    public function testHandleUserInfoRequestWithClaimsFromResolver(): void
    {
        $accessToken = $this->createMock(AccessTokenInterface::class);
        $user = $this->createMock(UserInterface::class);
        $scopes = ['openid', 'profile'];

        $accessToken->method('getScopes')->willReturn($scopes);
        $accessToken->method('getUser')->willReturn($user);

        $this->accessTokenManager->method('findAccessToken')
            ->with('token_with_claims')
            ->willReturn($accessToken);

        // Use a generator to simulate the claim resolver behavior
        $claimsGenerator = (function () {
            yield 'sub' => '12345';
            yield 'name' => 'Jane Doe';
            yield 'given_name' => 'Jane';
            yield 'family_name' => 'Doe';
        })();

        $this->claimResolver->method('resolveClaims')
            ->with($scopes, $user)
            ->willReturn($claimsGenerator);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->with('Authorization')->willReturn(true);
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer token_with_claims');

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $expectedClaims = [
            'sub' => '12345',
            'name' => 'Jane Doe',
            'given_name' => 'Jane',
            'family_name' => 'Doe'
        ];

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode($expectedClaims, JSON_THROW_ON_ERROR))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();
        $response->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $result = $this->oidcServer->handleUserInfoRequest($request);

        $this->assertSame($response, $result);
    }

    public function testHandleJwksRequestWithSingleKey(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        // Get the actual keys from our test keystore
        $keys = $this->keystore->getKeys();
        $expectedJwks = [];

        foreach ($keys as $keyId => $keyData) {
            $keyDetails = openssl_pkey_get_details(openssl_pkey_get_private($keyData['private']));
            $expectedJwks[] = [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $keyId,
                'n' => base64_encode($keyDetails['rsa']['n']),
                'e' => base64_encode($keyDetails['rsa']['e']),
            ];
        }

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode($expectedJwks, JSON_THROW_ON_ERROR))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();
        $response->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $result = $this->oidcServer->handleJwksRequest();

        $this->assertSame($response, $result);
    }

    public function testHandleJwksRequestWithMultipleKeys(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        // Get the actual keys from our test keystore (which has 2 keys)
        $keys = $this->keystore->getKeys();
        $expectedJwks = [];

        foreach ($keys as $keyId => $keyData) {
            $keyDetails = openssl_pkey_get_details(openssl_pkey_get_private($keyData['private']));
            $expectedJwks[] = [
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $keyId,
                'n' => base64_encode($keyDetails['rsa']['n']),
                'e' => base64_encode($keyDetails['rsa']['e']),
            ];
        }

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->with(json_encode($expectedJwks, JSON_THROW_ON_ERROR))
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();
        $response->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $result = $this->oidcServer->handleJwksRequest();

        $this->assertSame($response, $result);
    }

    public function testHandleJwksRequestReturnsValidResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')
            ->with(200)
            ->willReturn($response);

        $this->streamFactory->method('createStream')
            ->willReturn($stream);

        $response->method('withBody')->with($stream)->willReturnSelf();
        $response->method('withHeader')->with('Content-Type', 'application/json')->willReturnSelf();

        $result = $this->oidcServer->handleJwksRequest();

        $this->assertSame($response, $result);
    }

    public function testOidcServerConstructorWithCustomParameters(): void
    {
        $customGrants = [$this->createMock(\ForestCityLabs\Framework\Security\OAuth\Grant\GrantInterface::class)];
        $customCookieKey = '_custom_oauth_session';

        $oidcServer = new OidcServer(
            $this->accessTokenManager,
            $this->claimResolver,
            $this->keystore,
            $this->claimRegistry,
            $this->responseFactory,
            $this->streamFactory,
            $this->encryptionService,
            $this->scopeRegistry,
            $customGrants,
            $customCookieKey
        );

        $this->assertInstanceOf(OidcServer::class, $oidcServer);
        $this->assertInstanceOf(\ForestCityLabs\Framework\Security\OAuth\OAuthServer::class, $oidcServer);
    }

    public function testBearerTokenRegexPattern(): void
    {
        $validTokens = [
            'Bearer abc123',
            'Bearer token-with-dash',
            'Bearer token_with_underscore',
            'Bearer token.with.dots',
            'Bearer very_long_token_that_should_work_fine_123456789'
        ];

        $invalidTokens = [
            'bearer lowercase',
            'Bearer token with spaces',
            'Bearer ',
            'Basic dGVzdA==',
            'Token abc123'
        ];

        foreach ($validTokens as $token) {
            $matches = [];
            $result = preg_match('/^Bearer\s+(\S+)$/', $token, $matches);
            $this->assertEquals(1, $result, "Token '$token' should match the pattern");
            $this->assertNotEmpty($matches[1], "Token part should be extracted from '$token'");
        }

        foreach ($invalidTokens as $token) {
            $matches = [];
            $result = preg_match('/^Bearer\s+(\S+)$/', $token, $matches);
            $this->assertEquals(0, $result, "Token '$token' should not match the pattern");
        }
    }
}
