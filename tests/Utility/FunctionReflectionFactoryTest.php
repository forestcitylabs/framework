<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility;

use ForestCityLabs\Framework\Tests\Controller\TestController;
use ForestCityLabs\Framework\Utility\FunctionReflectionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

#[CoversClass(FunctionReflectionFactory::class)]
class FunctionReflectionFactoryTest extends TestCase
{
    #[Test]
    public function testReflectionFactory(): void
    {
        $reflection = FunctionReflectionFactory::createReflection(function () {
        });
        $this->assertInstanceOf(ReflectionFunction::class, $reflection);

        $reflection = FunctionReflectionFactory::createReflection([
            TestController::class,
            'test'
        ]);
        $this->assertInstanceOf(ReflectionMethod::class, $reflection);

        $reflection = FunctionReflectionFactory::createReflection(new TestController());
        $this->assertInstanceOf(ReflectionMethod::class, $reflection);

        $reflection = FunctionReflectionFactory::createReflection('str_contains');
        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
    }

    #[Test]
    public function invalidCallable(): void
    {
        $this->expectExceptionMessage("Invalid callable.");
        FunctionReflectionFactory::createReflection('not_a_function');
    }

    #[Test]
    public function invalidMethod(): void
    {
        $this->expectExceptionMessage("Invalid callable.");
        FunctionReflectionFactory::createReflection([new TestController(), 'not_a_function']);
    }
}
