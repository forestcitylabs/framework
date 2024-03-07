<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Routing\Collection;

use ForestCityLabs\Framework\Routing\Attribute\Route;
use ForestCityLabs\Framework\Routing\Collection\RouteCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteCollection::class)]
class RouteCollectionTest extends TestCase
{
    #[Test]
    public function collection(): void
    {
        $collection = new RouteCollection();
        $this->assertEquals(Route::class, $collection->getType());
    }
}
