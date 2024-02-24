<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\DefaultCacheMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(DefaultCacheMiddleware::class)]
class DefaultCacheMiddlewareTest extends TestCase
{
    private function generateMocks(): array
    {
        return [
            new ServerRequest(),
            $this->createMock(RequestHandlerInterface::class),
        ];
    }

    #[Test]
    public function cacheableResponseWithDefaultMaxAge(): void
    {
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $response = new Response(200, []);
        $handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $middleware = new DefaultCacheMiddleware();
        $response = $middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('cache-control'));
        $this->assertEquals('max-age=86400', $response->getHeaderLine('cache-control'));
    }

    #[Test]
    public function cacheableResponseWithCustomMaxAge(): void
    {
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $response = new Response(200, []);
        $handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $middleware = new DefaultCacheMiddleware(2400);
        $response = $middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('cache-control'));
        $this->assertEquals('max-age=2400', $response->getHeaderLine('cache-control'));
    }

    #[Test]
    public function responseWithUncacheableResponseCode(): void
    {
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $response = new Response(404, []);
        $handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $middleware = new DefaultCacheMiddleware();
        $response = $middleware->process($request, $handler);

        $this->assertTrue(!$response->hasHeader('cache-control'));
    }

    #[Test]
    public function requestWithUncacheableMethod(): void
    {
        $request = new ServerRequest("POST", new Uri("http://example.com/test"));
        $response = new Response(200, []);
        $handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $middleware = new DefaultCacheMiddleware();
        $response = $middleware->process($request, $handler);

        $this->assertTrue(!$response->hasHeader('cache-control'));
    }

    #[Test]
    public function responseWithCacheControlHeader(): void
    {
        $request = new ServerRequest("GET", new Uri("http://example.com/test"));
        $response = new Response(200, ['cache-control' => 'no-cache']);
        $handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

        $middleware = new DefaultCacheMiddleware();
        $response = $middleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('cache-control'));
        $this->assertTrue(!str_contains($response->getHeaderLine('cache-control'), 'max-age'));
    }
}
