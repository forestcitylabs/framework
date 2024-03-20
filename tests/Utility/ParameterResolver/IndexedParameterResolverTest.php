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
    #[DoesNotPerformAssertions]
    public function cantResolveParameters(): void
    {
        $reflection = new ReflectionMethod(ParameterConverterNegatives::class, 'cantConvert');
        $resolver = new IndexedParameterResolver();

        $resolver->resolveParameters($reflection, [
            $this->createMock(EntityManagerInterface::class),
        ]);
    }
}
