<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Event\Attribute;

use Attribute;
use ForestCityLabs\Framework\Event\ListenerProvider;

#[Attribute(Attribute::TARGET_CLASS)]
class EventListener
{
    public function __construct(
        private string $event_class,
        private int $priority = ListenerProvider::PRIORITY_NORMAL
    ) {
    }

    public function getEventClass(): string
    {
        return $this->event_class;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
