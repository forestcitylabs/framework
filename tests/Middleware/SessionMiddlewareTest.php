<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use Dflydev\FigCookies\Cookies;
use ForestCityLabs\Framework\Middleware\SessionMiddleware;
use ForestCityLabs\Framework\Session\Session;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(SessionMiddleware::class)]
#[Group('middleware')]
#[Group('session')]
#[UsesClass(Cookies::class)]
#[UsesClass(Session::class)]
class SessionMiddlewareTest extends TestCase
{
    #[Test]
    public function noExistingSessionWithNothingAdded(): void
    {
        // Stub our services.
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));

        // Configure our stubs.
        $handler->method('handle')->willReturn(new Response());

        // Create the middleware.
        $middleware = new SessionMiddleware();

        // Run the middleware.
        $middleware->process($request, $handler);

        // Get headers.
        $headers = headers_list();

        // Ensure that the response is cacheable.
        $this->assertEquals([], $headers);
        $this->assertNotContains('Set-Cookie: PHPSESSID=', $headers);
        $this->assertNotContains('Cache-Control: no-store, no-cache, must-revalidate', $headers);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function noExistingSessionWithAddedValues(): void
    {
        // Stub our services.
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));

        // Configure our stubs.
        $handler->method('handle')->willReturnCallback(function (ServerRequestInterface $request) {
            $session = Session::fromRequest($request);
            $session->setValue('test', 'value');
            return new Response();
        });

        // Create the middleware.
        $middleware = new SessionMiddleware();

        // Run the middleware.
        $middleware->process($request, $handler);
    }
}
