<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ClassDiscovery;

use ForestCityLabs\Framework\Utility\ClassDiscovery\ChainedDiscovery;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ManualDiscovery;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ScanDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChainedDiscovery::class)]
#[UsesClass(ManualDiscovery::class)]
#[UsesClass(ScanDirectory::class)]
#[Group('utilities')]
#[Group('class_discovery')]
class ChainedDiscoveryTest extends TestCase
{
    #[Test]
    public function discoverClasses(): void
    {
        $discovery = new ChainedDiscovery([
            new ManualDiscovery([__CLASS__]),
            new ScanDirectory(__DIR__),
        ]);
        $classes = $discovery->discoverClasses();
        $this->assertSame(__CLASS__, $classes[0]);
    }
}
