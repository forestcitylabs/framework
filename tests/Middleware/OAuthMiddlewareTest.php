<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\OAuthMiddleware;
use ForestCityLabs\Framework\Security\OAuth\OAuthServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(OAuthMiddleware::class)]
#[Group('oauth')]
#[Group('middleware')]
class OAuthMiddlewareTest extends TestCase
{
    private OAuthServer $oauthServer;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;
    private UriInterface $uri;

    protected function setUp(): void
    {
        $this->oauthServer = $this->createMock(OAuthServer::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        $this->request->method('getUri')->willReturn($this->uri);
    }

    #[Test]
    public function handlesAuthorizationRequest()
    {
        $redirectPath = '/callback';
        $middleware = new OAuthMiddleware($redirectPath, $this->oauthServer);

        $authResponse = $this->createMock(ResponseInterface::class);
        $authResponse->method('withStatus')->with(302)->willReturnSelf();
        $authResponse->method('withHeader')->with('Location', $redirectPath)->willReturnSelf();

        $this->uri->method('getPath')->willReturn('/oauth/authorize');

        $this->oauthServer
            ->expects($this->once())
            ->method('handleAuthorizationRequest')
            ->with($this->request)
            ->willReturn($authResponse);

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($authResponse, $response);
    }

    #[Test]
    public function handlesTokenRequest()
    {
        $redirectPath = '/callback';
        $middleware = new OAuthMiddleware($redirectPath, $this->oauthServer);

        $tokenResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn('/oauth/token');

        $this->oauthServer
            ->expects($this->once())
            ->method('handleTokenRequest')
            ->with($this->request)
            ->willReturn($tokenResponse);

        $this->handler->expects($this->never())->method('handle');

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($tokenResponse, $response);
    }

    #[Test]
    public function passesRequestToHandlerForNonOAuthPaths()
    {
        $redirectPath = '/callback';
        $middleware = new OAuthMiddleware($redirectPath, $this->oauthServer);

        $this->uri->method('getPath')->willReturn('/api/users');

        $this->oauthServer->expects($this->never())->method('handleAuthorizationRequest');
        $this->oauthServer->expects($this->never())->method('handleTokenRequest');

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
        $redirectPath = '/callback';
        $customAuthPath = '/custom/auth';
        $middleware = new OAuthMiddleware($redirectPath, $this->oauthServer, $customAuthPath);

        $authResponse = $this->createMock(ResponseInterface::class);
        $authResponse->method('withStatus')->with(302)->willReturnSelf();
        $authResponse->method('withHeader')->with('Location', $redirectPath)->willReturnSelf();

        $this->uri->method('getPath')->willReturn($customAuthPath);

        $this->oauthServer
            ->expects($this->once())
            ->method('handleAuthorizationRequest')
            ->with($this->request)
            ->willReturn($authResponse);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($authResponse, $response);
    }

    #[Test]
    public function usesCustomTokenPath()
    {
        $redirectPath = '/callback';
        $customTokenPath = '/custom/token';
        $middleware = new OAuthMiddleware(
            $redirectPath,
            $this->oauthServer,
            '/oauth/authorize',
            $customTokenPath
        );

        $tokenResponse = $this->createMock(ResponseInterface::class);

        $this->uri->method('getPath')->willReturn($customTokenPath);

        $this->oauthServer
            ->expects($this->once())
            ->method('handleTokenRequest')
            ->with($this->request)
            ->willReturn($tokenResponse);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($tokenResponse, $response);
    }

    #[Test]
    public function defaultAuthPathIsIgnoredWhenCustomPathSet()
    {
        $redirectPath = '/callback';
        $customAuthPath = '/custom/auth';
        $middleware = new OAuthMiddleware($redirectPath, $this->oauthServer, $customAuthPath);

        $this->uri->method('getPath')->willReturn('/oauth/authorize');

        $this->oauthServer->expects($this->never())->method('handleAuthorizationRequest');
        $this->oauthServer->expects($this->never())->method('handleTokenRequest');

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $response);
    }

    #[Test]
    public function defaultTokenPathIsIgnoredWhenCustomPathSet()
    {
        $redirectPath = '/callback';
        $customTokenPath = '/custom/token';
        $middleware = new OAuthMiddleware(
            $redirectPath,
            $this->oauthServer,
            '/oauth/authorize',
            $customTokenPath
        );

        $this->uri->method('getPath')->willReturn('/oauth/token');

        $this->oauthServer->expects($this->never())->method('handleAuthorizationRequest');
        $this->oauthServer->expects($this->never())->method('handleTokenRequest');

        $this->handler
            ->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $response);
    }
}
