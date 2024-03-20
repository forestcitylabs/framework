<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterResolver;

use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Utility\ParameterResolver\ChainedParameterResolver;
use ForestCityLabs\Framework\Utility\ParameterResolver\ParameterResolverInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(ChainedParameterResolver::class)]
class ChainedParameterResolverTest extends TestCase
{
    #[Test]
    public function resolve(): void
    {
        $resolver_one = $this->createMock(ParameterResolverInterface::class);
        $resolver_one->expects($this->once())->method('resolveParameters');
        $resolver_two = $this->createMock(ParameterResolverInterface::class);
        $resolver_two->expects($this->once())->method('resolveParameters');

        $reflection = new ReflectionMethod(AppleController::class, 'addApple');
        $resolver = new ChainedParameterResolver([$resolver_one, $resolver_two]);
        $resolver->resolveParameters($reflection, []);
    }
}
