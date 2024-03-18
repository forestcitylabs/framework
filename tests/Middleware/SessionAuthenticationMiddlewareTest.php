<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use DateTimeImmutable;
use Dflydev\FigCookies\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Middleware\SessionAuthenticationMiddleware;
use ForestCityLabs\Framework\Security\AccessTokenManagerInterface;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\Repository\AccessTokenRepositoryInterface;
use ForestCityLabs\Framework\Security\Repository\RefreshTokenRepositoryInterface;
use ForestCityLabs\Framework\Session\Session;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

#[CoversClass(SessionAuthenticationMiddleware::class)]
class SessionAuthenticationMiddlewareTest extends TestCase
{
    #[Test]
    public function noPathMatch(): void
    {
        // Mock the services.
        $access_token_repository = $this->createMock(AccessTokenRepositoryInterface::class);
        $refresh_token_respository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $token_manager = $this->createMock(AccessTokenManagerInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Mock the values.
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $uri = $this->createMock(UriInterface::class);

        // Configure the values.
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/beans');
        $request->expects($this->never())->method('getAttribute')->with('_session');

        // Create the middleware.
        $middleware = new SessionAuthenticationMiddleware($access_token_repository, $refresh_token_respository, $token_manager, $em, '/^\/path.*/');

        // Run the middleware.
        $middleware->process($request, $handler);
    }

    #[Test]
    public function noTokenInSession(): void
    {
        // Mock the services.
        $access_token_repository = $this->createMock(AccessTokenRepositoryInterface::class);
        $refresh_token_respository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $token_manager = $this->createMock(AccessTokenManagerInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Mock the values.
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $session = $this->createMock(Session::class);

        // Configure the values.
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/beans');
        $request->method('getAttribute')->with('_session')->willReturn($session);
        $request->method('getHeaderLine')->with('Cookie')->willReturn('');
        $request->expects($this->once())->method('getAttribute')->with('_session');

        // Create the middleware.
        $middleware = new SessionAuthenticationMiddleware($access_token_repository, $refresh_token_respository, $token_manager, $em);

        // Run the middleware.
        $middleware->process($request, $handler);
    }

    #[Test]
    public function validTokenInSession(): void
    {
        // Mock the services.
        $access_token_repository = $this->createMock(AccessTokenRepositoryInterface::class);
        $refresh_token_respository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $token_manager = $this->createMock(AccessTokenManagerInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Mock the values.
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $session = $this->createMock(Session::class);
        $access_token = $this->createMock(AccessTokenInterface::class);
        $uuid = Uuid::uuid4();

        // Configure the values.
        $request->method('getAttribute')->with('_session')->willReturn($session);
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/');
        $session->method('getValue')->with('_access_token')->willReturn($uuid->toString());
        $request->expects($this->once())->method('withAttribute')->with('_access_token', $access_token)->willReturnSelf();
        $access_token->method('getExpiry')->willReturn(new DateTimeImmutable('+1 day'));

        // Configure the services.
        $access_token_repository->method('findToken')->with($uuid->toString())->willReturn($access_token);

        // Create the middleware.
        $middleware = new SessionAuthenticationMiddleware(
            $access_token_repository,
            $refresh_token_respository,
            $token_manager,
            $em
        );

        // Run the middleware.
        $middleware->process($request, $handler);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function refreshTokenInCookies(): void
    {
        // Mock the services.
        $access_token_repository = $this->createMock(AccessTokenRepositoryInterface::class);
        $refresh_token_respository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $token_manager = $this->createMock(AccessTokenManagerInterface::class);
        $em = $this->createMock(EntityManagerInterface::class);

        // Mock the values.
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $session = $this->createMock(Session::class);
        $uuid = Uuid::uuid4();
        $refresh_token = $this->createMock(RefreshTokenInterface::class);
        $access_token = $this->createMock(AccessTokenInterface::class);
        $response = $this->createMock(Response::class);

        // Configure the values.
        $request->method('getAttribute')->with('_session')->willReturn($session);
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/');
        $request->method('getHeaderLine')->with('Cookie')->willReturn(Cookie::create('_refresh_token', $uuid->toString())->__toString());
        $refresh_token->method('getExpiry')->willReturn(new DateTimeImmutable('+1 day'));
        $handler->method('handle')->willReturn($response);
        $response->method('withAddedHeader')->willReturnSelf();
        $request->method('withAttribute')->willReturnSelf();

        // Configure the services.
        $refresh_token_respository->method('findToken')->with($uuid->toString())->willReturn($refresh_token);
        $token_manager->method('exchangeRefreshToken')->with($refresh_token)->willReturn($access_token);
        $token_manager->method('generateRefreshToken')->with($access_token)->willReturn($this->createMock(RefreshTokenInterface::class));

        // Create the middleware.
        $middleware = new SessionAuthenticationMiddleware(
            $access_token_repository,
            $refresh_token_respository,
            $token_manager,
            $em
        );

        // Run the middleware.
        $middleware->process($request, $handler);
    }
}
