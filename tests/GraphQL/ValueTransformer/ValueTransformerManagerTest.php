<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\ValueTransformer;

use DateTimeImmutable;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\DateTimeImmutableValueTransformer;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use PHPUnit\Framework\TestCase;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ValueTransformerManager::class)]
#[UsesClass(DateTimeImmutableValueTransformer::class)]
class ValueTransformerManagerTest extends TestCase
{
    #[Test]
    public function transformerManager(): void
    {
        $manager = new ValueTransformerManager([
            $this->createStub(ValueTransformerInterface::class),
            new DateTimeImmutableValueTransformer(),
        ]);
        $this->assertEquals('String', $manager->getGraphQLType(DateTimeImmutable::class));
        $this->assertInstanceOf(DateTimeImmutableValueTransformer::class, $manager->getTransformer(DateTimeImmutable::class));
    }
}
