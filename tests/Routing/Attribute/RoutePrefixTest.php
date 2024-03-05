<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Routing\Attribute;

use ForestCityLabs\Framework\Routing\Attribute\RoutePrefix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoutePrefix::class)]
class RoutePrefixTest extends TestCase
{
    #[Test]
    public function routePrefix(): void
    {
        $prefix = new RoutePrefix("/prefix");
        $this->assertEquals("/prefix", $prefix->getPath());
    }
}
