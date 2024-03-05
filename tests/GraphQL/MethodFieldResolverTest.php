<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\MethodFieldResolver;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;

#[CoversClass(MethodFieldResolver::class)]
class MethodFieldResolverTest extends TestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function resolveField(): void
    {
        // Mock the services.
        $container = $this->createStub(ContainerInterface::class);
        $processor = $this->createStub(ParameterProcessor::class);
        $transformer = $this->createStub(ValueTransformerInterface::class);

        // Create the values.
        $field = $this->createConfiguredStub(Field::class, [
            'getAttributeName' => AppleController::class .  '::getApple',
        ]);
        $container->method('get')->with(AppleController::class)->willReturn(
            $this->createStub(AppleController::class)
        );
        $processor->method('processParameters')->willReturn([
            'id' => Uuid::uuid4(),
            'em' => $this->createStub(EntityManagerInterface::class)
        ]);

        // Resolve a field.
        $resolver = new MethodFieldResolver($container, $processor, $transformer);
        $resolver->resolveField($field);
    }
}
