<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use Dflydev\FigCookies\Cookies;
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
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(SessionMiddleware::class)]
#[UsesClass(Cookies::class)]
#[UsesClass(Session::class)]
class SessionMiddlewareTest extends TestCase
{
    #[Test]
    public function noExistingSessionWithNothingAdded(): void
    {
        // Stub our services.
        $driver = $this->createStub(SessionDriverInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));

        // Configure our stubs.
        $handler->method('handle')->willReturn(new Response());

        // Create the middleware.
        $middleware = new SessionMiddleware($driver);

        // Run the middleware.
        $response = $middleware->process($request, $handler);

        // Ensure that the response is cacheable.
        $this->assertEquals([], $response->getHeader('cache-control'));
    }
}
