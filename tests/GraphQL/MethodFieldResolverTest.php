<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Events\PreGraphQLFieldResolveEvent;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\MethodFieldResolver;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

#[CoversClass(MethodFieldResolver::class)]
#[UsesClass(PreGraphQLFieldResolveEvent::class)]
#[Group('graphql')]
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
        $dispatcher = $this->createStub(EventDispatcherInterface::class);

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
        $resolver = new MethodFieldResolver($container, $processor, $transformer, $dispatcher);
        $resolver->resolveField($field, request: $this->createStub(ServerRequestInterface::class));
    }
}
