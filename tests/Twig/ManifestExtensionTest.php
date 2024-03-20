<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Twig;

use ForestCityLabs\Framework\Twig\ManifestExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

#[CoversClass(ManifestExtension::class)]
class ManifestExtensionTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'manifest.json';
        file_put_contents($this->path, json_encode(["style.css" => "/beans/style.css"]));
    }
    #[Test]
    public function extension(): void
    {
        $extension = new ManifestExtension($this->path);
        $this->assertEquals("/beans/style.css", $extension->getEntry('style.css'));
        $this->assertInstanceOf(TwigFunction::class, $extension->getFunctions()[0]);
    }
}
