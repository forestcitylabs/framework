<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Twig;

use ForestCityLabs\Framework\Twig\ViteManifestExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(ViteManifestExtension::class)]
class ViteManifestExtensionTest extends TestCase
{
    private string $manifestPath;

    protected function setUp(): void
    {
        $this->manifestPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vite-manifest.json';
        file_put_contents($this->manifestPath, json_encode([
            'src/main.ts' => [
                'file' => 'assets/main.4889e940.js',
                'isEntry' => true,
                'css' => ['assets/main.b82dbe22.css', 'assets/chunk.abc123.css'],
            ],
            'src/other.ts' => [
                'file' => 'assets/other.xyz789.js',
                'isEntry' => true,
            ],
        ]));
    }

    #[Test]
    public function prodModeScriptTagsContainHashedFilename(): void
    {
        $extension = new ViteManifestExtension(devMode: false, manifestPath: $this->manifestPath);

        $html = $extension->getScriptTags('src/main.ts');

        $this->assertStringContainsString('<script type="module"', $html);
        $this->assertStringContainsString('assets/main.4889e940.js', $html);
    }

    #[Test]
    public function prodModeLinkTagsContainAllAssociatedCssFiles(): void
    {
        $extension = new ViteManifestExtension(devMode: false, manifestPath: $this->manifestPath);

        $html = $extension->getLinkTags('src/main.ts');

        $this->assertStringContainsString('<link rel="stylesheet"', $html);
        $this->assertStringContainsString('assets/main.b82dbe22.css', $html);
        $this->assertStringContainsString('assets/chunk.abc123.css', $html);
    }

    #[Test]
    public function prodModeLinkTagsReturnsEmptyStringWhenNoCss(): void
    {
        $extension = new ViteManifestExtension(devMode: false, manifestPath: $this->manifestPath);

        $html = $extension->getLinkTags('src/other.ts');

        $this->assertSame('', $html);
    }

    #[Test]
    public function prodModeMissingEntryReturnsEmptyString(): void
    {
        $extension = new ViteManifestExtension(devMode: false, manifestPath: $this->manifestPath);

        $this->assertSame('', $extension->getScriptTags('src/missing.ts'));
        $this->assertSame('', $extension->getLinkTags('src/missing.ts'));
    }

    #[Test]
    public function devModeScriptTagsPointToDevServer(): void
    {
        $extension = new ViteManifestExtension(devMode: true, devServerUrl: 'http://localhost:5173');

        $html = $extension->getScriptTags('src/main.ts');

        $this->assertStringContainsString('http://localhost:5173/@vite/client', $html);
        $this->assertStringContainsString('http://localhost:5173/src/main.ts', $html);
    }

    #[Test]
    public function devModeInjectsClientScriptOnlyOnce(): void
    {
        $extension = new ViteManifestExtension(devMode: true, devServerUrl: 'http://localhost:5173');

        $first = $extension->getScriptTags('src/main.ts');
        $second = $extension->getScriptTags('src/other.ts');

        $combined = $first . $second;
        $this->assertSame(1, substr_count($combined, '@vite/client'));
    }

    #[Test]
    public function devModeLinkTagsReturnsEmptyString(): void
    {
        $extension = new ViteManifestExtension(devMode: true, devServerUrl: 'http://localhost:5173');

        $this->assertSame('', $extension->getLinkTags('src/main.ts'));
    }

    #[Test]
    public function functionsOutputRawHtmlWithoutEscaping(): void
    {
        $extension = new ViteManifestExtension(devMode: false, manifestPath: $this->manifestPath);

        $twig = new Environment(new ArrayLoader([
            'test.html' => '{{ vite_entry_script_tags("src/main.ts") }}{{ vite_entry_link_tags("src/main.ts") }}',
        ]));
        $twig->addExtension($extension);

        $output = $twig->render('test.html');

        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('<link', $output);
        $this->assertStringNotContainsString('&lt;', $output);
    }
}
