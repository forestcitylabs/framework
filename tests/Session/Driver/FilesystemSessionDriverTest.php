<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Session\Driver;

use ForestCityLabs\Framework\Session\Driver\FilesystemSessionDriver;
use ForestCityLabs\Framework\Session\Session;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(FilesystemSessionDriver::class)]
#[UsesClass(Session::class)]
#[Group("session")]
class FilesystemSessionDriverTest extends AbstractSessionDriverTestCase
{
    protected function setUp(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->driver = new FilesystemSessionDriver($filesystem);
    }
}
