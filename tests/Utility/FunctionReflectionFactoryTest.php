<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility;

use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use ForestCityLabs\Framework\Tests\Fixture\Miscellaneous\InvokableClass;
use ForestCityLabs\Framework\Utility\FunctionReflectionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;

#[CoversClass(FunctionReflectionFactory::class)]
#[Group("utilities")]
class FunctionReflectionFactoryTest extends TestCase
{
    #[Test]
    public function anonymousFunction(): void
    {
        $reflection = FunctionReflectionFactory::createReflection(function () {
        });
        $this->assertInstanceOf(ReflectionFunction::class, $reflection);
    }

    #[Test]
    public function classMethod(): void
    {
        $reflection = FunctionReflectionFactory::createReflection([
            UserController::class,
            'login'
        ]);
        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
    }

    #[Test]
    public function invokableClass(): void
    {
        $reflection = FunctionReflectionFactory::createReflection(new InvokableClass());
        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
    }

    #[Test]
    public function standardFunction(): void
    {
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
        FunctionReflectionFactory::createReflection([UserController::class, 'not_a_function']);
    }
}
