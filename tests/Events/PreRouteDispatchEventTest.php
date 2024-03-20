<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Events;

use ForestCityLabs\Framework\Events\PreRouteDispatchEvent;
use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(PreRouteDispatchEvent::class)]
class PreRouteDispatchEventTest extends TestCase
{
    #[Test]
    public function event(): void
    {
        $event = new PreRouteDispatchEvent(UserController::class, 'login', $this->createStub(ServerRequestInterface::class));
        $this->assertEquals(UserController::class, $event->getController());
        $this->assertEquals('login', $event->getMethod());
        $this->assertInstanceOf(ServerRequestInterface::class, $event->getRequest());
        $this->assertEquals(null, $event->getResponse());
        $event->setResponse($this->createStub(ResponseInterface::class));
        $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
    }
}
