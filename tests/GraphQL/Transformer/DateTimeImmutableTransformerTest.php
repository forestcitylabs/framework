<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\ValueTransformer;

use DateTimeImmutable;
use ForestCityLabs\Framework\GraphQL\Transformer\DateTimeImmutableTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTimeImmutableTransformer::class)]
class DateTimeImmutableTransformerTest extends TestCase
{
    private const DATE = '2024-07-13T09:30:00-04:00';

    #[Test]
    public function transformOutput(): void
    {
        $transformer = new DateTimeImmutableTransformer();
        $date = new DateTimeImmutable(self::DATE);
        $this->assertEquals($transformer->transformOutput($date), self::DATE);
    }

    #[Test]
    public function transformInput(): void
    {
        $transformer = new DateTimeImmutableTransformer();
        $this->assertEquals(new DateTimeImmutable(self::DATE), $transformer->transformInput(self::DATE));
    }

    #[Test]
    public function getTypes(): void
    {
        $transformer = new DateTimeImmutableTransformer();
        $this->assertEquals('String', $transformer->getGraphQLType());
    }
}
