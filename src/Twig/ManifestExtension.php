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

class ManifestExtension extends AbstractExtension
{
    private array $manifest;

    public function __construct(string $manifest_path)
    {
        $this->manifest = json_decode(file_get_contents($manifest_path), true);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('manifest_entry', [$this, 'getEntry']),
        ];
    }

    public function getEntry(string $entry)
    {
        return $this->manifest[$entry] ?? null;
    }
}
