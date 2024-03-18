<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ClassDiscovery;

use ForestCityLabs\Framework\Utility\ClassDiscovery\ScanDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScanDirectory::class)]
#[Group("utilities")]
#[Group("class_discovery")]
class ScanDirectoryTest extends TestCase
{
    #[Test]
    public function discoverClasses(): void
    {
        $discovery = new ScanDirectory(__DIR__);
        $classes = $discovery->discoverClasses();
        $this->assertSame(__CLASS__, $classes[0]);
    }
}
