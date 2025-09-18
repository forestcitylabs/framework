<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\OidcMiddleware;
use ForestCityLabs\Framework\Security\Oidc\OidcServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(OidcMiddleware::class)]
#[Group('oidc')]
#[Group('middleware')]
class OidcMiddlewareTest extends TestCase
{
    private OidcServer $oidcServer;
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;
    private StreamInterface $stream;
    private UriInterface $uri;

    protected function setUp(): void
    {
        $this->oidcServer = $this->createMock(OidcServer::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        $this->request->method('getUri')->willReturn($this->uri);
    }

    #[Test]
    public function handlesAuthorizationRequest()
    {
        $redirectUri = '/callback';
        $middleware = new OidcMiddleware($redirectUri, $this->responseFactory, $this->streamFactory, $this->oidcServer);

        $authResponse = $this->createMock(ResponseInterface::class);
        $authResponse->method('withStatus')->with(302)->willReturnSelf();
        $authResponse->method('withHeader')->with('Location', $redirectUri)->willReturnSelf();

        $this->uri->method('getPath')->willReturn('/oauth/authorize');

        $this->oidcServer
            ->expects($this->once())
            ->method('handleAuthorizationRequest')
            ->with($this->request, '/callback')
            ->willReturn($authResponse);

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($authResponse, $response);
    }

    #[Test]
    public function handlesTokenRequest()
    {
        $redirectUri = '/callback';
        $middleware = new OidcMiddleware($redirectUri, $this->responseFactory, $this->streamFactory, $this->oidcServer);

        $tokenResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn('/oauth/token');

        $this->oidcServer
            ->expects($this->once())
            ->method('handleTokenRequest')
            ->with($this->request)
            ->willReturn($tokenResponse);

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($tokenResponse, $response);
    }

    #[Test]
    public function handlesUserInfoRequest()
    {
        $redirectUri = '/callback';
        $middleware = new OidcMiddleware($redirectUri, $this->responseFactory, $this->streamFactory, $this->oidcServer);

        $userInfoResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn('/oauth/userinfo');

        $this->oidcServer
            ->expects($this->once())
            ->method('handleUserInfoRequest')
            ->with($this->request)
            ->willReturn($userInfoResponse);

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($userInfoResponse, $response);
    }

    #[Test]
    public function handlesJwksRequest()
    {
        $redirectUri = '/callback';
        $middleware = new OidcMiddleware($redirectUri, $this->responseFactory, $this->streamFactory, $this->oidcServer);

        $jwksResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn('/oauth/jwks.json');

        $this->oidcServer
            ->expects($this->once())
            ->method('handleJwksRequest')
            ->willReturn($jwksResponse);

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($jwksResponse, $response);
    }

    #[Test]
    public function handlesOpenIdConfigurationDiscovery()
    {
        $redirectUri = '/callback';
        $middleware = new OidcMiddleware($redirectUri, $this->responseFactory, $this->streamFactory, $this->oidcServer);

        $this->uri->method('getPath')->willReturn('/.well-known/openid-configuration');
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('withPath')->willReturnCallback(function ($path) {
            $newUri = $this->createMock(UriInterface::class);
            $newUri->method('__toString')->willReturn('https://example.com' . $path);
            return $newUri;
        });

        $expectedConfig = [
            'issuer' => 'https://example.com',
            'authorization_endpoint' => 'https://example.com/oauth/authorize',
            'token_endpoint' => 'https://example.com/oauth/token',
            'userinfo_endpoint' => 'https://example.com/oauth/userinfo',
            'jwks_uri' => 'https://example.com/oauth/jwks.json',
            'response_types_supported' => ['code', 'token', 'id_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
        ];

        $this->streamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(json_encode($expectedConfig))
            ->willReturn($this->stream);

        $this->responseFactory
            ->expects($this->once())
            ->method('createResponse')
            ->with(200)
            ->willReturn($this->response);

        $this->response
            ->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('withBody')
            ->with($this->stream)
            ->willReturnSelf();

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $response);
    }

    #[Test]
    public function passesRequestToHandlerForNonOidcPaths()
    {
        $redirectUri = '/callback';
        $middleware = new OidcMiddleware($redirectUri, $this->responseFactory, $this->streamFactory, $this->oidcServer);

        $this->uri->method('getPath')->willReturn('/api/users');

        $this->oidcServer->expects($this->never())->method('handleAuthorizationRequest');
        $this->oidcServer->expects($this->never())->method('handleTokenRequest');
        $this->oidcServer->expects($this->never())->method('handleUserInfoRequest');
        $this->oidcServer->expects($this->never())->method('handleJwksRequest');

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $response);
    }

    #[Test]
    public function usesCustomAuthPath()
    {
        $redirectUri = '/callback';
        $customAuthPath = '/custom/auth';
        $middleware = new OidcMiddleware(
            $redirectUri,
            $this->responseFactory,
            $this->streamFactory,
            $this->oidcServer,
            $customAuthPath
        );

        $authResponse = $this->createMock(ResponseInterface::class);
        $authResponse->method('withStatus')->with(302)->willReturnSelf();
        $authResponse->method('withHeader')->with('Location', $redirectUri)->willReturnSelf();

        $this->uri->method('getPath')->willReturn($customAuthPath);

        $this->oidcServer
            ->expects($this->once())
            ->method('handleAuthorizationRequest')
            ->with($this->request, '/callback')
            ->willReturn($authResponse);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($authResponse, $response);
    }

    #[Test]
    public function usesCustomTokenPath()
    {
        $redirectUri = '/callback';
        $customTokenPath = '/custom/token';
        $middleware = new OidcMiddleware(
            $redirectUri,
            $this->responseFactory,
            $this->streamFactory,
            $this->oidcServer,
            '/oauth/authorize',
            $customTokenPath
        );

        $tokenResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn($customTokenPath);

        $this->oidcServer
            ->expects($this->once())
            ->method('handleTokenRequest')
            ->with($this->request)
            ->willReturn($tokenResponse);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($tokenResponse, $response);
    }

    #[Test]
    public function usesCustomUserInfoPath()
    {
        $redirectUri = '/callback';
        $customUserInfoPath = '/custom/userinfo';
        $middleware = new OidcMiddleware(
            $redirectUri,
            $this->responseFactory,
            $this->streamFactory,
            $this->oidcServer,
            '/oauth/authorize',
            '/oauth/token',
            $customUserInfoPath
        );

        $userInfoResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn($customUserInfoPath);

        $this->oidcServer
            ->expects($this->once())
            ->method('handleUserInfoRequest')
            ->with($this->request)
            ->willReturn($userInfoResponse);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($userInfoResponse, $response);
    }

    #[Test]
    public function usesCustomJwksPath()
    {
        $redirectUri = '/callback';
        $customJwksPath = '/custom/jwks';
        $middleware = new OidcMiddleware(
            $redirectUri,
            $this->responseFactory,
            $this->streamFactory,
            $this->oidcServer,
            '/oauth/authorize',
            '/oauth/token',
            '/oauth/userinfo',
            $customJwksPath
        );

        $jwksResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn($customJwksPath);

        $this->oidcServer
            ->expects($this->once())
            ->method('handleJwksRequest')
            ->willReturn($jwksResponse);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($jwksResponse, $response);
    }

    #[Test]
    public function discoveryEndpointUsesCustomPaths()
    {
        $redirectUri = '/callback';
        $customAuthPath = '/custom/auth';
        $customTokenPath = '/custom/token';
        $customUserInfoPath = '/custom/userinfo';
        $customJwksPath = '/custom/jwks';

        $middleware = new OidcMiddleware(
            $redirectUri,
            $this->responseFactory,
            $this->streamFactory,
            $this->oidcServer,
            $customAuthPath,
            $customTokenPath,
            $customUserInfoPath,
            $customJwksPath
        );

        $this->uri->method('getPath')->willReturn('/.well-known/openid-configuration');
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('withPath')->willReturnCallback(function ($path) {
            $newUri = $this->createMock(UriInterface::class);
            $newUri->method('__toString')->willReturn('https://example.com' . $path);
            return $newUri;
        });

        $expectedConfig = [
            'issuer' => 'https://example.com',
            'authorization_endpoint' => 'https://example.com' . $customAuthPath,
            'token_endpoint' => 'https://example.com' . $customTokenPath,
            'userinfo_endpoint' => 'https://example.com' . $customUserInfoPath,
            'jwks_uri' => 'https://example.com' . $customJwksPath,
            'response_types_supported' => ['code', 'token', 'id_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
        ];

        $this->streamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with(json_encode($expectedConfig))
            ->willReturn($this->stream);

        $this->responseFactory
            ->expects($this->once())
            ->method('createResponse')
            ->with(200)
            ->willReturn($this->response);

        $this->response
            ->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $this->response
            ->expects($this->once())
            ->method('withBody')
            ->with($this->stream)
            ->willReturnSelf();

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $response);
    }
}
