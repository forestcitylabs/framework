<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Routing\Attribute;

use ForestCityLabs\Framework\Routing\Attribute\Route;
use ForestCityLabs\Framework\Routing\Attribute\RoutePrefix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
#[UsesClass(RoutePrefix::class)]
class RouteTest extends TestCase
{
    #[Test]
    public function minimalRoute(): void
    {
        // Test creation and route.
        $route = new Route("/path");
        $this->assertEquals("/path", $route->getPath());

        // Test using route name.
        $this->assertEquals(null, $route->getName());
        $route->setName("test_path");
        $this->assertEquals("test_path", $route->getName());

        // Test methods on a vanilla route.
        $this->assertEquals(["GET"], $route->getMethods());

        // Test class and methods.
        $this->assertEquals(null, $route->getClassName());
        $this->assertEquals(null, $route->getMethodName());
        $route->setClassName("class");
        $route->setMethodName("method");
        $this->assertEquals("class", $route->getClassName());
        $this->assertEquals("method", $route->getMethodName());

        // Test route prefix.
        $this->assertEquals(null, $route->getPrefix());
        $prefix = new RoutePrefix("/prefix");
        $route->setPrefix($prefix);
        $this->assertEquals($prefix, $route->getPrefix());
    }

    #[Test]
    public function fullRoute(): void
    {
        $route = new Route("/path", ["POST"], "named");
        $this->assertEquals(["POST"], $route->getMethods());
        $this->assertEquals("named", $route->getName());
    }

    #[Test]
    public function serialize(): void
    {
        $route = new Route("/route", ['POST'], 'namer');
        $data = serialize($route);
        $route = unserialize($data);
        $this->assertEquals("/route", $route->getPath());
    }
}
