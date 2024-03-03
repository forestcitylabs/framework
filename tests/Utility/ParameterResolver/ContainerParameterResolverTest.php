<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterResolver;

use ForestCityLabs\Framework\Tests\Controller\TestController;
use ForestCityLabs\Framework\Utility\ParameterResolver\ContainerParameterResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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
        $container->method('get')->with(TestController::class)->willReturn(new TestController());

        // Create the test values.
        $reflection = new ReflectionMethod(TestController::class, 'serviceParameter');

        // Test the resolver.
        $resolver = new ContainerParameterResolver($container);
        $args = $resolver->resolveParameters($reflection, ['test' => 'value']);

        // Make some assertions.
        $this->assertInstanceOf(TestController::class, $args['controller']);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function resolveInvalidParameters(): void
    {
        // Mock the services.
        $container = $this->createStub(ContainerInterface::class);
        $exception = $this->createMock(NotFoundExceptionInterface::class);
        $container->method('get')->with(TestController::class)->willThrowException($exception);

        // Create the test values.
        $reflection = new ReflectionMethod(TestController::class, 'serviceParameter');

        // Test the resolver.
        $resolver = new ContainerParameterResolver($container);
        $resolver->resolveParameters($reflection, ['test' => 'value']);
    }
}
