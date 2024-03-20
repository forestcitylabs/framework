<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ORM;

use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use ForestCityLabs\Framework\Utility\ORM\EntityListenerResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(EntityListenerResolver::class)]
class EntityListenerResolverTest extends TestCase
{
    #[Test]
    public function resolve(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(UserController::class)
            ->willReturn($this->createMock(UserController::class));
        $resolver = new EntityListenerResolver($container);
        $resolver->resolve(UserController::class);
    }
}
