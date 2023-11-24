<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use Dflydev\FigCookies\Cookies;
use ForestCityLabs\Framework\Middleware\CookiesMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(CookiesMiddleware::class)]
class CookiesMiddlewareTest extends TestCase
{
    #[Test]
    public function process()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('withAttribute')
            ->with('_cookies')
            ->willReturnSelf();
        $request->method('getHeaderLine')
            ->with(Cookies::COOKIE_HEADER)
            ->willReturn('');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request);
        $middleware = new CookiesMiddleware();
        $middleware->process($request, $handler);
    }
}
