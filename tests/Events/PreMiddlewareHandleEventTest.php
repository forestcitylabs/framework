<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Events;

use ForestCityLabs\Framework\Events\PreMiddlewareHandleEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\MiddlewareInterface;

#[CoversClass(PreMiddlewareHandleEvent::class)]
#[Group('events')]
class PreMiddlewareHandleEventTest extends TestCase
{
    #[Test]
    public function event(): void
    {
        $event = new PreMiddlewareHandleEvent(
            $this->createStub(MiddlewareInterface::class),
            $this->createStub(RequestInterface::class)
        );

        $this->assertInstanceOf(MiddlewareInterface::class, $event->getMiddleware());
        $this->assertInstanceOf(RequestInterface::class, $event->getRequest());
    }
}
