<?php

declare(strict_types=1);

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
        // Create the token.
        $token = $this->createConfiguredStub(AccessTokenInterface::class, [
            'getExpiry' => new DateTimeImmutable('+1 day'),
        ]);

        // Configure the repo.
        $repo = $this->createConfiguredStub(AccessTokenRepositoryInterface::class, [
            'findToken' => $token,
        ]);

        // Create the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('Authorization')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('Authorization')
            ->willReturn(['Bearer test-token']);
        $request->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);

        // We are asserting that the _access_token is added to the request.
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('_access_token', $token)
            ->willReturn($request);

        // Assert that the handler is called eventually.
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);

        // Test the middleware.
        $middleware = new BearerTokenAuthenticationMiddleware($repo);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function expiredToken()
    {
        // Create and configure the token.
        $token = $this->createStub(AccessTokenInterface::class);
        $token->method('getExpiry')
            ->willReturn(new DateTimeImmutable('-1 day'));

        // Configure the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('Authorization')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('Authorization')
            ->willReturn(['Bearer test-token']);
        $request->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);

        // Assert that we never add the _access_token attribute.
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
        $repo = $this->createStub(AccessTokenRepositoryInterface::class);
        $repo->method('findToken')
            ->with('test-token')
            ->willReturn($token);

        // Process the middleware.
        $middleware = new BearerTokenAuthenticationMiddleware($repo);
        $middleware->process($request, $handler);
    }

    #[Test]
    public function tokenNotFound()
    {
        // Configure the request.
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')
            ->with('Authorization')
            ->willReturn(true);
        $request->method('getHeader')
            ->with('Authorization')
            ->willReturn(['Bearer test-token']);
        $request->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);

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
