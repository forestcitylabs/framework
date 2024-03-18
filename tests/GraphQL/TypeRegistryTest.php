<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\EnumType;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\GraphQL\MethodFieldResolver;
use ForestCityLabs\Framework\GraphQL\PropertyFieldResolver;
use ForestCityLabs\Framework\GraphQL\TypeRegistry;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Controller\BasketController;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use ForestCityLabs\Framework\Tests\Fixture\Entity\AppleTypeEnum;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Basket;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

#[CoversClass(TypeRegistry::class)]
#[UsesClass(MetadataProvider::class)]
#[UsesClass(Argument::class)]
#[UsesClass(Field::class)]
#[UsesClass(ObjectType::class)]
#[UsesClass(AbstractType::class)]
#[UsesClass(EnumType::class)]
#[UsesClass(ManualDiscovery::class)]
#[Group("graphql")]
class TypeRegistryTest extends TestCase
{
    #[Test]
    public function getType(): void
    {
        $item = $this->createConfiguredStub(CacheItemInterface::class, [
            'isHit' => false,
        ]);
        $item->method('set')->willReturnSelf();
        $cache = $this->createConfiguredStub(CacheItemPoolInterface::class, [
            'getItem' => $item,
        ]);

        $provider = new MetadataProvider(new ManualDiscovery([
            Apple::class,
            Basket::class,
            AppleTypeEnum::class,
        ]), new ManualDiscovery([
            AppleController::class,
            BasketController::class,
        ]), $cache);

        $registry = new TypeRegistry($provider, $this->createStub(PropertyFieldResolver::class), $this->createStub(MethodFieldResolver::class));

        // Get all the types.
        $this->assertEquals(Type::string(), $registry->getType('String'));
        $this->assertEquals(Type::int(), $registry->getType('Int'));
        $this->assertEquals(Type::boolean(), $registry->getType('Boolean'));
        $this->assertEquals(Type::float(), $registry->getType('Float'));
        $this->assertEquals(Type::id(), $registry->getType('ID'));
        $apple = $registry->getType('Apple');
        $apple->getFields();
        $input = $registry->getType('AppleInput');
        $input->getFields();
        $registry->getType('AppleInput');
        $registry->getType('Basket');
        $registry->getType('BasketInput');
        $registry->getType('Mutation');
        $registry->getType('Object');
    }
}
