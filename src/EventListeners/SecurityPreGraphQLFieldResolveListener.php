<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\EventListeners;

use ForestCityLabs\Framework\Event\Attribute\EventListener;
use ForestCityLabs\Framework\Events\PreGraphQLFieldResolveEvent;
use ForestCityLabs\Framework\Security\RequirementChecker;

#[EventListener(PreGraphQLFieldResolveEvent::class)]
class SecurityPreGraphQLFieldResolveListener
{
    public function __construct(
        private RequirementChecker $checker
    ) {
    }

    public function __invoke(PreGraphQLFieldResolveEvent $event)
    {
        $this->checker->checkRequirements($event->getContext(), $event->getRequest());
    }
}
