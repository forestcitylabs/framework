<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function json_decode;
use function sprintf;

class ViteManifestExtension extends AbstractExtension
{
    private array $manifest = [];
    private bool $clientInjected = false;

    public function __construct(
        private readonly bool $devMode,
        private readonly string $devServerUrl = 'http://localhost:5173',
        private readonly string $manifestPath = '',
    ) {
        if (!$devMode && $manifestPath !== '') {
            $this->manifest = json_decode(file_get_contents($manifestPath), true);
        }
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'getScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'getLinkTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_react_refresh', [$this, 'getReactRefresh'], ['is_safe' => ['html']]),
        ];
    }

    public function getScriptTags(string $entry): string
    {
        if ($this->devMode) {
            $tags = '';
            if (!$this->clientInjected) {
                $tags .= sprintf('<script type="module" src="%s/@vite/client"></script>', $this->devServerUrl);
                $this->clientInjected = true;
            }
            $tags .= sprintf('<script type="module" src="%s/%s"></script>', $this->devServerUrl, ltrim($entry, '/'));
            return $tags;
        }

        $manifestEntry = $this->manifest[$entry] ?? null;
        if ($manifestEntry === null) {
            return '';
        }

        return sprintf('<script type="module" src="/%s"></script>', $manifestEntry['file']);
    }

    public function getReactRefresh(): string
    {
        if (!$this->devMode) {
            return '';
        }

        return sprintf(
            '<script type="module">import RefreshRuntime from "%s/@react-refresh";' .
            'RefreshRuntime.injectIntoGlobalHook(window);' .
            'window.$RefreshReg$=()=>{};window.$RefreshSig$=()=>()=>{};' .
            'window.__vite_plugin_react_preamble_installed__=true;</script>',
            $this->devServerUrl
        );
    }

    public function getLinkTags(string $entry): string
    {
        if ($this->devMode) {
            return '';
        }

        $manifestEntry = $this->manifest[$entry] ?? null;
        if ($manifestEntry === null) {
            return '';
        }

        $tags = '';
        foreach ($manifestEntry['css'] ?? [] as $cssFile) {
            $tags .= sprintf('<link rel="stylesheet" href="/%s">', $cssFile);
        }

        return $tags;
    }
}
