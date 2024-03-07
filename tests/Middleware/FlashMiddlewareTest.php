<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Middleware\FlashMiddleware;
use ForestCityLabs\Framework\Session\Flash;
use ForestCityLabs\Framework\Session\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(FlashMiddleware::class)]
#[UsesClass(Session::class)]
#[UsesClass(Flash::class)]
#[Group('session')]
class FlashMiddlewareTest extends TestCase
{
    #[Test]
    public function noSession(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);

        $middleware = new FlashMiddleware();

        $this->expectExceptionMessage('Session middleware is required for flash middlewares.');
        $middleware->process($request, $handler);
    }

    #[Test]
    public function noFlash(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $session = $this->createStub(Session::class);

        $request->method('getAttribute')->with('_session')->willReturn($session);
        $request->expects($this->once())->method('withAttribute')->willReturnSelf();
        $middleware = new FlashMiddleware();
        $middleware->process($request, $handler);
    }

    #[Test]
    public function existingFlashEmpty(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $session = $this->createStub(Session::class);
        $session->method('hasValue')->with('_flash')->willReturn(true);
        $flash = new Flash();
        $session->method('getValue')->with('_flash')->willReturn($flash);

        $request->method('getAttribute')->with('_session')->willReturn($session);
        $request->expects($this->once())->method('withAttribute')->willReturnSelf();
        $middleware = new FlashMiddleware();
        $middleware->process($request, $handler);
    }

    #[Test]
    public function existingFlashNonEmpty(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $session = $this->createStub(Session::class);
        $session->method('hasValue')->with('_flash')->willReturn(true);
        $flash = new Flash();
        $flash->addFlash('success', 'test');
        $session->method('getValue')->with('_flash')->willReturn($flash);

        $request->method('getAttribute')->with('_session')->willReturn($session);
        $request->expects($this->once())->method('withAttribute')->willReturnSelf();
        $middleware = new FlashMiddleware();
        $middleware->process($request, $handler);
    }
}
