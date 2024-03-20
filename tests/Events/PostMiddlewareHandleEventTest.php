<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Events;

use ForestCityLabs\Framework\Events\PostMiddlewareHandleEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

#[CoversClass(PostMiddlewareHandleEvent::class)]
#[Group('events')]
class PostMiddlewareHandleEventTest extends TestCase
{
    #[Test]
    public function event(): void
    {
        $event = new PostMiddlewareHandleEvent(
            $this->createStub(MiddlewareInterface::class),
            $this->createStub(RequestInterface::class),
            $this->createStub(ResponseInterface::class)
        );

        $this->assertInstanceOf(MiddlewareInterface::class, $event->getMiddleware());
        $this->assertInstanceOf(RequestInterface::class, $event->getRequest());
        $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
    }
}
