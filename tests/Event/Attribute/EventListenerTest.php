<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Event\Attribute;

use ForestCityLabs\Framework\Event\Attribute\EventListener;
use ForestCityLabs\Framework\Event\ListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventListener::class)]
#[Group("event")]
class EventListenerTest extends TestCase
{
    #[Test]
    public function eventListener(): void
    {
        $listener = new EventListener("test", ListenerProvider::PRIORITY_LAST);
        $this->assertEquals("test", $listener->getEventClass());
        $this->assertEquals(ListenerProvider::PRIORITY_LAST, $listener->getPriority());
    }
}
