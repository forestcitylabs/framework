<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\EnumType;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Attribute\InputType;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType;
use ForestCityLabs\Framework\GraphQL\Attribute\Value;
use ForestCityLabs\Framework\GraphQL\InputResolver;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Controller\BasketController;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use ForestCityLabs\Framework\Tests\Fixture\Entity\AppleTypeEnum;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Basket;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\SchemaFields\AbstractField;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[CoversClass(InputResolver::class)]
#[UsesClass(AbstractField::class)]
#[UsesClass(Argument::class)]
#[UsesClass(AbstractType::class)]
#[UsesClass(InputType::class)]
#[UsesClass(Field::class)]
#[UsesClass(MetadataProvider::class)]
#[UsesClass(ObjectType::class)]
#[UsesClass(ManualDiscovery::class)]
#[UsesClass(EnumType::class)]
#[UsesClass(Value::class)]
#[Group("graphql")]
class InputResolverTest extends TestCase
{
    #[Test]
    public function resolveValid(): void
    {
        // Mock the services.
        $accessor = new PropertyAccessor();
        $cache = $this->createStub(CacheItemPoolInterface::class);
        $item = $this->createStub(CacheItemInterface::class);
        $item->method('set')->willReturnSelf();
        $cache->method('getItem')->willReturn($item);
        $provider = new MetadataProvider(new ManualDiscovery([
            Apple::class,
            Basket::class,
            AppleTypeEnum::class,
        ]), new ManualDiscovery([
            AppleController::class,
            BasketController::class
        ]), $cache);
        $em = $this->createStub(EntityManagerInterface::class);

        // Create the input resolver.
        $resolver = new InputResolver($accessor, $provider, $em);

        // Mock the values to resolve.
        $values = [
            'type' => 'macintosh',
        ];

        // Activate the resolver.
        $object = $resolver->resolve($values, $provider->getTypeMetadata('AppleInput'));

        // Make assertions.
        $this->assertInstanceOf(Apple::class, $object);
    }
}
