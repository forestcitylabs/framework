<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\AbstractField;
use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectField;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Tests\Controller\TestController;
use ForestCityLabs\Framework\Tests\Entity\AnotherTestEntity;
use ForestCityLabs\Framework\Tests\Entity\TestEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

#[CoversClass(MetadataProvider::class)]
#[UsesClass(AbstractField::class)]
#[UsesClass(AbstractType::class)]
#[UsesClass(ObjectField::class)]
#[UsesClass(Argument::class)]
class MetadataProviderTest extends TestCase
{
    private MetadataProvider $metadata_provider;

    public function init()
    {
        $item = $this->createConfiguredStub(CacheItemInterface::class, [
            'isHit' => false,
        ]);
        $item->method('set')->willReturnSelf();
        $cache = $this->createConfiguredStub(CacheItemPoolInterface::class, [
            'getItem' => $item,
        ]);
        $this->metadata_provider = new MetadataProvider([
            TestEntity::class,
            AnotherTestEntity::class,
        ], [
            TestController::class,
        ], $cache);
    }

    #[Test]
    public function getTypeMetadata()
    {
        $this->init();

        $valid = $this->metadata_provider->getTypeMetadata('TestEntity');
        $this->assertInstanceOf(AbstractType::class, $valid);

        $invalid = $this->metadata_provider->getTypeMetadata('nope');
        $this->assertEquals($invalid, null);
    }

    #[Test]
    public function getObjectTypeMetadataByClassName()
    {
        $this->init();

        $valid = $this->metadata_provider->getObjectTypeMetadataByClassName(TestEntity::class);
        $this->assertInstanceOf(AbstractType::class, $valid);

        $invalid = $this->metadata_provider->getObjectTypeMetadataByClassName('nope');
        $this->assertEquals(null, $invalid);
    }

    #[Test]
    public function getInputTypeMetadataByClassName()
    {
        $this->init();

        $valid = $this->metadata_provider->getInputTypeMetadataByClassName(TestEntity::class);
        $this->assertInstanceOf(AbstractType::class, $valid);

        $invalid = $this->metadata_provider->getInputTypeMetadataByClassName('nope');
        $this->assertEquals(null, $invalid);
    }
}
