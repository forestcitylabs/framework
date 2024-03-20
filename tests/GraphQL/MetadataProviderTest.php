<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\EnumType;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType;
use ForestCityLabs\Framework\GraphQL\Attribute\Value;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Controller\BasketController;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use ForestCityLabs\Framework\Tests\Fixture\Entity\AppleTypeEnum;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Basket;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Fruit;
use ForestCityLabs\Framework\Tests\Fixture\Entity\RegionEnum;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Spatie\Snapshots\MatchesSnapshots;

#[CoversClass(MetadataProvider::class)]
#[UsesClass(AbstractType::class)]
#[UsesClass(Argument::class)]
#[UsesClass(Field::class)]
#[UsesClass(ObjectType::class)]
#[UsesClass(EnumType::class)]
#[UsesClass(ManualDiscovery::class)]
#[UsesClass(Value::class)]
#[Group('graphql')]
class MetadataProviderTest extends TestCase
{
    use MatchesSnapshots;

    #[Test]
    public function buildMetadata(): void
    {
        $item = $this->createConfiguredStub(CacheItemInterface::class, [
            'isHit' => false,
        ]);
        $item->method('set')->willReturnSelf();
        $cache = $this->createConfiguredStub(CacheItemPoolInterface::class, [
            'getItem' => $item,
        ]);
        $metadata_provider = new MetadataProvider(new ManualDiscovery([
            Apple::class,
            Basket::class,
            AppleTypeEnum::class,
            RegionEnum::class,
            Fruit::class,
        ]), new ManualDiscovery([
            AppleController::class,
            BasketController::class,
        ]), $cache);
        $this->assertMatchesSnapshot($metadata_provider->getAllTypeMetadata());

        $valid = $metadata_provider->getTypeMetadata('Apple');
        $this->assertInstanceOf(AbstractType::class, $valid);

        $valid = $metadata_provider->getObjectTypeMetadataByClassName(Apple::class);
        $this->assertInstanceOf(AbstractType::class, $valid);

        $valid = $metadata_provider->getInputTypeMetadataByClassName(Apple::class);
        $this->assertInstanceOf(AbstractType::class, $valid);

        $invalid = $metadata_provider->getInputTypeMetadataByClassName('nope');
        $this->assertEquals(null, $invalid);
    }
}
