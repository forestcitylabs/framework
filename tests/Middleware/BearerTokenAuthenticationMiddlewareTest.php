<?php

namespace ForestCityLabs\Framework\Tests\Middleware;

use DateTimeImmutable;
use ForestCityLabs\Framework\Middleware\BearerTokenAuthenticationMiddleware;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Repository\AccessTokenRepositoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(BearerTokenAuthenticationMiddleware::class)]
#[Group('authorization')]
#[Group('middleware')]
class BearerTokenAuthenticationMiddlewareTest extends TestCase
{
    #[Test]
    public function validToken()
    {
        // Configure the token.
        $token = $this->createMock(AccessTokenInterface::class);
        $token->expects($this->exactly(1))
            ->method('getExpiry')
            ->willReturn(new DateTimeImmutable('+1 day'));

        // Configure the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('hasHeader')
            ->with('Authorization')
            ->willReturn(true);
        $request->expects($this->exactly(3))
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn(['Bearer test-token']);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('_access_token', $token)
            ->willReturn($request);

        // Configure the handler.
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);

        // Configure the repo.
        $repo = $this->createMock(AccessTokenRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findToken')
            ->with('test-token')
            ->willReturn($token);
        $middleware = new BearerTokenAuthenticationMiddleware($repo);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function expiredToken()
    {
        // Configure the token.
        $token = $this->createMock(AccessTokenInterface::class);
        $token->expects($this->exactly(1))
            ->method('getExpiry')
            ->willReturn(new DateTimeImmutable('-1 day'));

        // Configure the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('hasHeader')
            ->with('Authorization')
            ->willReturn(true);
        $request->expects($this->exactly(3))
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn(['Bearer test-token']);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);
        $request->expects($this->never())
            ->method('withAttribute')
            ->with('_access_token', $token)
            ->willReturn($request);

        // Configure the handler.
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);

        // Configure the repo.
        $repo = $this->createMock(AccessTokenRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findToken')
            ->with('test-token')
            ->willReturn($token);
        $middleware = new BearerTokenAuthenticationMiddleware($repo);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function tokenNotFound()
    {
        // Configure the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('hasHeader')
            ->with('Authorization')
            ->willReturn(true);
        $request->expects($this->exactly(3))
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn(['Bearer test-token']);
        $request->expects($this->once())
            ->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);
        $request->expects($this->never())
            ->method('withAttribute')
            ->with('_access_token', $token)
            ->willReturn($request);

        // Configure the handler.
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);

        // Configure the repo.
        $repo = $this->createMock(AccessTokenRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findToken')
            ->with('test-token')
            ->willReturn(null);
        $middleware = new BearerTokenAuthenticationMiddleware($repo);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function noToken()
    {
        // Configure the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('hasHeader')
            ->with('Authorization')
            ->willReturn(false);

        // Configure the handler.
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);

        // Configure the repo.
        $repo = $this->createMock(AccessTokenRepositoryInterface::class);
        $middleware = new BearerTokenAuthenticationMiddleware($repo);
        $middleware->process($request, $handler);
    }
}
