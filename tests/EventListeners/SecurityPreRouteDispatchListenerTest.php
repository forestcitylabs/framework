<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\EntityListeners;

use ForestCityLabs\Framework\EventListeners\SecurityPreRouteDispatchListener;
use ForestCityLabs\Framework\Events\PreRouteDispatchEvent;
use ForestCityLabs\Framework\Security\Exception\ForbiddenException;
use ForestCityLabs\Framework\Security\Exception\InsufficientScopeException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\RequirementChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;

#[CoversClass(SecurityPreRouteDispatchListener::class)]
#[UsesClass(UnauthorizedException::class)]
#[UsesClass(InsufficientScopeException::class)]
#[UsesClass(ForbiddenException::class)]
class SecurityPreRouteDispatchListenerTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function requirementsMet(): void
    {
        // Mock services.
        $checker = $this->createStub(RequirementChecker::class);
        $factory = $this->createStub(ResponseFactoryInterface::class);

        // Create the listener.
        $listener = new SecurityPreRouteDispatchListener($checker, $factory);

        // Invoke the listener.
        $listener($this->createStub(PreRouteDispatchEvent::class));
    }

    #[Test]
    public function unauthorized(): void
    {
        // Mock services.
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->with(401);
        $checker = $this->createStub(RequirementChecker::class);
        $checker
            ->method('checkRequirements')
            ->willThrowException(new UnauthorizedException());

        // Create the listener.
        $listener = new SecurityPreRouteDispatchListener($checker, $factory);

        // Invoke the listener.
        $listener($this->createStub(PreRouteDispatchEvent::class));
    }

    #[Test]
    public function insufficientScope(): void
    {
        // Mock services.
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->with(403, 'Insufficient scope');
        $checker = $this->createStub(RequirementChecker::class);
        $checker
            ->method('checkRequirements')
            ->willThrowException(new InsufficientScopeException());

        // Create the listener.
        $listener = new SecurityPreRouteDispatchListener($checker, $factory);

        // Invoke the listener.
        $listener($this->createStub(PreRouteDispatchEvent::class));
    }

    #[Test]
    public function forbidden(): void
    {
        // Mock services.
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('createResponse')
            ->with(403);
        $checker = $this->createStub(RequirementChecker::class);
        $checker
            ->method('checkRequirements')
            ->willThrowException(new ForbiddenException());

        // Create the listener.
        $listener = new SecurityPreRouteDispatchListener($checker, $factory);

        // Invoke the listener.
        $listener($this->createStub(PreRouteDispatchEvent::class));
    }
}
