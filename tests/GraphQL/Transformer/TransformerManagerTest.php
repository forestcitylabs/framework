<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Transformer;

use DateTimeImmutable;
use ForestCityLabs\Framework\GraphQL\Transformer\DateTimeImmutableTransformer;
use ForestCityLabs\Framework\GraphQL\Transformer\TransformerInterface;
use ForestCityLabs\Framework\GraphQL\Transformer\TransformerManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(TransformerManager::class)]
#[UsesClass(DateTimeImmutableTransformer::class)]
class TransformerManagerTest extends TestCase
{
    #[Test]
    public function transformerManager(): void
    {
        $manager = new TransformerManager([
            $this->createStub(TransformerInterface::class),
            new DateTimeImmutableTransformer(),
        ]);
        $this->assertEquals('String', $manager->getGraphQLType(DateTimeImmutable::class));
        $this->assertInstanceOf(DateTimeImmutableTransformer::class, $manager->getTransformer(DateTimeImmutable::class));
    }
}
