<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterResolver;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Miscellaneous\ParameterConverterNegatives;
use ForestCityLabs\Framework\Utility\ParameterResolver\IndexedParameterResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(IndexedParameterResolver::class)]
class IndexedParameterResolverTest extends TestCase
{
    #[Test]
    public function resolveParameters(): void
    {
        $reflection = new ReflectionMethod(AppleController::class, 'addApple');
        $resolver = new IndexedParameterResolver();

        $args = $resolver->resolveParameters($reflection, [
            $this->createMock(EntityManagerInterface::class),
        ]);

        $this->assertInstanceOf(EntityManagerInterface::class, $args['em']);

        $args = $resolver->resolveParameters($reflection, []);
        $this->assertEmpty($args);
    }

    #[Test]
    public function cantResolveParameters(): void
    {
        $reflection = new ReflectionMethod(ParameterConverterNegatives::class, 'cantConvert');
        $resolver = new IndexedParameterResolver();

        $args = $resolver->resolveParameters($reflection, [
            $this->createMock(EntityManagerInterface::class),
        ]);

        // Should return empty array since the method has no type-compatible parameters
        $this->assertEmpty($args);
    }

    #[Test]
    public function resolveWithMixedArguments(): void
    {
        $reflection = new ReflectionMethod(AppleController::class, 'addApple');
        $resolver = new IndexedParameterResolver();

        $em = $this->createMock(EntityManagerInterface::class);
        $args = $resolver->resolveParameters($reflection, [
            'existing' => 'value',
            $em,
            'another' => 'param'
        ]);

        $this->assertEquals('value', $args['existing']);
        $this->assertEquals('param', $args['another']);
        $this->assertSame($em, $args['em']);
    }

    #[Test]
    public function skipParametersWithExistingValues(): void
    {
        $reflection = new ReflectionMethod(AppleController::class, 'addApple');
        $resolver = new IndexedParameterResolver();

        $existingEm = $this->createMock(EntityManagerInterface::class);
        $newEm = $this->createMock(EntityManagerInterface::class);

        $args = $resolver->resolveParameters($reflection, [
            'em' => $existingEm, // Already has value for 'em' parameter
            $newEm,
        ]);

        // Should keep the existing value, not replace it
        $this->assertSame($existingEm, $args['em']);
        $this->assertCount(1, $args); // Only the 'em' parameter should be resolved
    }
}
