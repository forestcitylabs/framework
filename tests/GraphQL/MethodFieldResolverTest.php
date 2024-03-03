<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\MethodFieldResolver;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use ForestCityLabs\Framework\Tests\Controller\TestController;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(MethodFieldResolver::class)]
class MethodFieldResolverTest extends TestCase
{
    #[Test]
    public function resolveField(): void
    {
        // Mock the services.
        $container = $this->createStub(ContainerInterface::class);
        $processor = $this->createStub(ParameterProcessor::class);
        $transformer = $this->createStub(ValueTransformerInterface::class);

        // Create the values.
        $field = $this->createConfiguredStub(Field::class, [
            'getAttributeName' => TestController::class .  '::uuidParameter',
        ]);
        $container->method('get')->with(TestController::class)->willReturn(new TestController());

        // Resolve a field.
        $resolver = new MethodFieldResolver($container, $processor, $transformer);
        $resolver->resolveField($field);
    }
}
