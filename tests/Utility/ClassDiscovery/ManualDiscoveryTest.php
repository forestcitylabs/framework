<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ClassDiscovery;

use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ManualDiscovery::class)]
#[Group('utilities')]
#[Group('class_discovery')]
class ManualDiscoveryTest extends TestCase
{
    #[Test]
    public function discoverClasses(): void
    {
        $discovery = new ManualDiscovery([__CLASS__]);
        $classes = $discovery->discoverClasses();
        $this->assertSame(__CLASS__, $classes[0]);
    }
}
