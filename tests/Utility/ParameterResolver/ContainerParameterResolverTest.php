<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterResolver;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Utility\ParameterResolver\ContainerParameterResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;

#[CoversClass(ContainerParameterResolver::class)]
#[Group("utilities")]
class ContainerParameterResolverTest extends TestCase
{
    #[Test]
    public function resolveValidParameters(): void
    {
        // Mock the services.
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->with(EntityManagerInterface::class)->willReturn(
            $this->createStub(EntityManagerInterface::class)
        );

        // Create the test values.
        $reflection = new ReflectionMethod(AppleController::class, 'getApple');

        // Test the resolver.
        $resolver = new ContainerParameterResolver($container);
        $args = $resolver->resolveParameters($reflection, ['id' => Uuid::uuid4()]);

        // Make some assertions.
        $this->assertInstanceOf(EntityManagerInterface::class, $args['em']);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function resolveInvalidParameters(): void
    {
        // Mock the services.
        $container = $this->createStub(ContainerInterface::class);
        $exception = $this->createMock(NotFoundExceptionInterface::class);
        $container->method('get')->with(EntityManagerInterface::class)->willThrowException($exception);

        // Create the test values.
        $reflection = new ReflectionMethod(AppleController::class, 'getApple');

        // Test the resolver.
        $resolver = new ContainerParameterResolver($container);
        $resolver->resolveParameters($reflection, ['id' => Uuid::uuid4()]);
    }
}
