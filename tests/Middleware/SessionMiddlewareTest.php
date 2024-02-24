<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use DateTime;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookies;
use ForestCityLabs\Framework\Middleware\SessionMiddleware;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

#[CoversClass(SessionMiddleware::class)]
#[UsesClass(Cookies::class)]
#[UsesClass(Session::class)]
class SessionMiddlewareTest extends TestCase
{
    #[Test]
    public function noExistingSessionWithNothingAdded(): void
    {
        // Stub our services.
        $driver = $this->createMock(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));

        // Configure our stubs.
        $handler->method('handle')->willReturn(new Response());

        // Assert that the driver is never called.
        $driver->expects($this->never())->method('save');
        $driver->expects($this->never())->method('delete');
        $driver->expects($this->never())->method('load');

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is cacheable.
        $this->assertEquals([], $response->getHeader('cache-control'));
    }

    #[Test]
    public function noExistingSessionWithAddedValues(): void
    {
        // Stub our services.
        $driver = $this->createMock(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));

        // Configure our stubs.
        $handler->method('handle')->willReturnCallback(function (ServerRequestInterface $request) {
            $session = Session::fromRequest($request);
            $session->setValue('test', 'value');
            return new Response();
        });

        // Assert that the driver attempts to save the session.
        $driver->expects($this->once())->method('save');

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is not cacheable.
        $this->assertEquals(['no-store, no-cache, must-revalidate'], $response->getHeader('cache-control'));

        // Ensure that a session cookie is created.
        $this->assertEquals(true, $response->hasHeader('set-cookie'));
        $this->assertEquals(true, SetCookies::fromResponse($response)->has('_session'));
    }

    #[Test]
    public function existingSessionNoAddedValues(): void
    {
        // Stub our services.
        $driver = $this->createMock(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $uuid = Uuid::uuid4();

        // Create our session cookie.
        $cookie = new Cookie('_session', (string) $uuid);
        $request = $request->withAddedHeader(Cookies::COOKIE_HEADER, (string) $cookie);

        // Create our session.
        $session = new Session($uuid);
        $session->setValue('test', 'value');
        $session->setExpiry(new DateTime('+1 day'));

        // Configure our stubs.
        $driver->expects($this->once())->method('load')->willReturn($session);
        $driver->expects($this->once())->method('save');
        $handler->method('handle')->willReturn(new Response());

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is cacheable.
        $this->assertEquals(['no-store, no-cache, must-revalidate'], $response->getHeader('cache-control'));
    }

    #[Test]
    public function existingSessionExpiredWithNoNewSession(): void
    {
        // Stub our services.
        $driver = $this->createMock(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $uuid = Uuid::uuid4();

        // Create our session cookie.
        $cookie = new Cookie('_session', (string) $uuid);
        $request = $request->withAddedHeader(Cookies::COOKIE_HEADER, (string) $cookie);

        // Create our session.
        $session = new Session($uuid);
        $session->setValue('test', 'value');
        $session->setExpiry(new DateTime('-1 day'));

        // Configure our stubs.
        $driver->expects($this->once())->method('load')->willReturn($session);
        $driver->expects($this->once())->method('delete');
        $handler->method('handle')->willReturn(new Response());

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is cacheable.
        $this->assertEquals(['no-store, no-cache, must-revalidate'], $response->getHeader('cache-control'));
        $this->assertEquals(true, SetCookies::fromResponse($response)->has('_session'));
        $this->assertLessThanOrEqual((new DateTime())->format('U'), SetCookies::fromResponse($response)->get('_session')->getExpires());
    }

    #[Test]
    public function existingSessionExpiredWithNewSession(): void
    {
        // Stub our services.
        $driver = $this->createMock(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $uuid = Uuid::uuid4();

        // Create our session cookie.
        $cookie = new Cookie('_session', (string) $uuid);
        $request = $request->withAddedHeader(Cookies::COOKIE_HEADER, (string) $cookie);

        // Create our session.
        $session = new Session($uuid);
        $session->setValue('test', 'value');
        $session->setExpiry(new DateTime('-1 day'));

        // Configure our stubs.
        $driver->expects($this->once())->method('load')->willReturn($session);
        $driver->expects($this->once())->method('delete');
        $handler->method('handle')->willReturnCallback(function (ServerRequestInterface $request) {
            $session = Session::fromRequest($request);
            $session->setValue('test', 'value');
            return new Response();
        });

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is cacheable.
        $this->assertEquals(['no-store, no-cache, must-revalidate'], $response->getHeader('cache-control'));
        $this->assertEquals(true, SetCookies::fromResponse($response)->has('_session'));
    }

    #[Test]
    public function invalidSessionCookie(): void
    {
        // Stub our services.
        $driver = $this->createMock(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $uuid = Uuid::uuid4();

        // Create our session cookie.
        $cookie = new Cookie('_session', 'test');
        $request = $request->withAddedHeader(Cookies::COOKIE_HEADER, (string) $cookie);

        // Create our session.
        $session = new Session($uuid);
        $session->setValue('test', 'value');
        $session->setExpiry(new DateTime('+1 day'));

        // Configure our stubs.
        $driver->expects($this->once())->method('save');
        $handler->method('handle')->willReturnCallback(function (ServerRequestInterface $request) {
            $session = Session::fromRequest($request);
            $session->setValue('test', 'value');
            return new Response();
        });

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is cacheable.
        $this->assertEquals(['no-store, no-cache, must-revalidate'], $response->getHeader('cache-control'));
    }
}
