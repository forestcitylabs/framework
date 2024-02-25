<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\ReturnNotFoundMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use Http\Factory\Guzzle\ResponseFactory;
use Http\Factory\Guzzle\StreamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(ReturnNotFoundMiddleware::class)]
class ReturnNotFoundMiddlewareTest extends TestCase
{
    #[Test]
    public function returnNotFound(): void
    {
        $middleware = new ReturnNotFoundMiddleware(
            new ResponseFactory(),
            new StreamFactory()
        );
        $response = $middleware->process(
            $this->createStub(ServerRequestInterface::class),
            $this->createStub(RequestHandlerInterface::class)
        );
        $this->assertSame(404, $response->getStatusCode());
    }
}
