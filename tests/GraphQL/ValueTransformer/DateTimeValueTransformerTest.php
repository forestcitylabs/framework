<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\ValueTransformer;

use DateTime;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\DateTimeValueTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTimeValueTransformer::class)]
#[Group('graphql')]
class DateTimeValueTransformerTest extends TestCase
{
    #[Test]
    public function transform(): void
    {
        $transformer = new DateTimeValueTransformer();
        $date = new DateTime();
        $output = $transformer->transformOutput($date);
        $this->assertEquals($date->format('c'), $output);
        $this->assertEquals('nope', $transformer->transformOutput('nope'));
    }
}
