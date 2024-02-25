<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\GeneratedByMiddleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(GeneratedByMiddleware::class)]
class GeneratedByMiddlewareTest extends TestCase
{
    #[Test]
    public function testGeneratedBy(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => new Response(),
        ]);

        $middleware = new GeneratedByMiddleware();
        $response = $middleware->process($request, $handler);
        $this->assertTrue($response->hasHeader('X-Generated-By'));
    }
}
