<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\EventListeners;

use ForestCityLabs\Framework\Event\Attribute\EventListener;
use ForestCityLabs\Framework\Events\PreRouteDispatchEvent;
use ForestCityLabs\Framework\Security\Exception\ForbiddenException;
use ForestCityLabs\Framework\Security\Exception\InsufficientScopeException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\RequirementChecker;
use Psr\Http\Message\ResponseFactoryInterface;

#[EventListener(PreRouteDispatchEvent::class)]
class SecurityPreRouteDispatchListener
{
    public function __construct(
        private RequirementChecker $requirement_checker,
        private ResponseFactoryInterface $response_factory
    ) {
    }

    public function __invoke(PreRouteDispatchEvent $event)
    {
        try {
            // Check the requirements for this route.
            $this->requirement_checker->checkRequirements(
                [$event->getController(), $event->getMethod()],
                $event->getRequest()
            );
        } catch (UnauthorizedException) {
            // Caught an unauthorized error.
            $event->setResponse($this->response_factory->createResponse(401));
        } catch (InsufficientScopeException) {
            // Caught an insufficient scope error.
            $event->setResponse($this->response_factory->createResponse(403, 'Insufficient scope'));
        } catch (ForbiddenException) {
            // Caught a forbidden error.
            $event->setResponse($this->response_factory->createResponse(403));
        }
    }
}
