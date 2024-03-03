<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Event;

use ForestCityLabs\Framework\Event\Attribute\EventListener;

#[EventListener(TestEvent::class)]
class TestEventListener
{
    public function __invoke(TestEvent $event): void
    {
    }
}
