<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\OAuth;

use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\Grant\GrantInterface;
use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use ForestCityLabs\Framework\Security\OAuth\OAuthServer;
use ForestCityLabs\Framework\Utility\EncryptionService;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[CoversClass(OAuthServer::class)]
#[Group('security')]
#[Group('oauth')]
#[UsesClass(AuthRequest::class)]
class OAuthServerTest extends TestCase
{
    public function testHandleValidAuthorizationRequest(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);
        $gr = $this->createMock(GrantInterface::class);
        $gr->method('canHandleAuthorizationRequest')
            ->willReturn(true);
        $gr->method('handleAuthorizationRequest')
            ->willReturn($this->createMock(AuthRequest::class));
        $rf->method('createResponse')
            ->willReturn(new Response());
        $server = new OAuthServer(
            $rf,
            $sf,
            $es,
            $sr,
            [$gr],
        );

        $response = $server->handleAuthorizationRequest(
            $this->createMock(ServerRequestInterface::class),
            '/oauth/callback'
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->hasHeader('Set-Cookie'));
    }

    public function testHandleAuthorizationRequestWithNoValidGrant(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);
        $gr = $this->createMock(GrantInterface::class);

        $gr->method('canHandleAuthorizationRequest')
            ->willReturn(false);

        $response = new Response(400);
        $rf->method('createResponse')
            ->with(400)
            ->willReturn($response);
        $sf->method('createStream')
            ->with('No valid grant found to handle the authorization request.')
            ->willReturn(new \GuzzleHttp\Psr7\Stream(fopen('data://text/plain;base64,' . base64_encode('No valid grant found to handle the authorization request.'), 'r')));

        $server = new OAuthServer($rf, $sf, $es, $sr, [$gr]);

        $result = $server->handleAuthorizationRequest(
            $this->createMock(ServerRequestInterface::class),
            '/oauth/callback'
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testHandleTokenRequest(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);
        $gr = $this->createMock(GrantInterface::class);
        $tokenResponse = $this->createMock(\ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse::class);

        $gr->method('canHandleTokenRequest')
            ->willReturn(true);
        $gr->method('handleTokenRequest')
            ->willReturn($tokenResponse);
        $tokenResponse->method('formatResponse')
            ->willReturn(['access_token' => 'test_token', 'token_type' => 'Bearer']);

        $response = new Response(200);
        $rf->method('createResponse')
            ->with(200)
            ->willReturn($response);
        $sf->method('createStream')
            ->willReturn(new \GuzzleHttp\Psr7\Stream(fopen('data://text/plain;base64,' . base64_encode('{"access_token":"test_token","token_type":"Bearer"}'), 'r')));

        $server = new OAuthServer($rf, $sf, $es, $sr, [$gr]);

        $result = $server->handleTokenRequest(
            $this->createMock(ServerRequestInterface::class)
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testHandleTokenRequestWithNoValidGrant(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);
        $gr = $this->createMock(GrantInterface::class);

        $gr->method('canHandleTokenRequest')
            ->willReturn(false);

        $response = new Response(400);
        $rf->method('createResponse')
            ->with(400)
            ->willReturn($response);
        $sf->method('createStream')
            ->with('No valid grant found to handle the token request.')
            ->willReturn(new \GuzzleHttp\Psr7\Stream(fopen('data://text/plain;base64,' . base64_encode('No valid grant found to handle the token request.'), 'r')));

        $server = new OAuthServer($rf, $sf, $es, $sr, [$gr]);

        $result = $server->handleTokenRequest(
            $this->createMock(ServerRequestInterface::class)
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }

    public function testGetAuthorizationRequestWithNoCookie(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);

        $server = new OAuthServer($rf, $sf, $es, $sr);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')
            ->with('Cookie')
            ->willReturn([]);

        $result = $server->getAuthorizationRequest($request);

        $this->assertNull($result);
    }

    public function testGetAuthorizationRequestWithValidCookie(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);

        $authRequest = new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            new \DateTimeImmutable('+1 hour')
        );

        $es->method('decrypt')
            ->willReturn(serialize($authRequest));

        $server = new OAuthServer($rf, $sf, $es, $sr);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->with('Cookie')
            ->willReturn('_oauth_session=encrypted_value');

        $result = $server->getAuthorizationRequest($request);

        $this->assertInstanceOf(AuthRequest::class, $result);
        $this->assertEquals('test_client', $result->getClientId());
    }

    public function testApproveAuthorizationRequestWithoutAuthorizationCodeGrant(): void
    {
        $rf = $this->createMock(ResponseFactoryInterface::class);
        $sf = $this->createMock(StreamFactoryInterface::class);
        $es = $this->createMock(EncryptionService::class);
        $sr = $this->createMock(OAuthScopeRegistry::class);
        $gr = $this->createMock(GrantInterface::class);

        $response = new Response(400);
        $rf->method('createResponse')
            ->with(400)
            ->willReturn($response);
        $sf->method('createStream')
            ->with('No valid grant found to approve the authorization request.')
            ->willReturn(new \GuzzleHttp\Psr7\Stream(fopen('data://text/plain;base64,' . base64_encode('No valid grant found to approve the authorization request.'), 'r')));

        $server = new OAuthServer($rf, $sf, $es, $sr, [$gr]);

        $user = $this->createMock(\ForestCityLabs\Framework\Security\Model\UserInterface::class);
        $result = $server->approveAuthorizationRequest(
            $this->createMock(ServerRequestInterface::class),
            $user
        );

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
    }
}
