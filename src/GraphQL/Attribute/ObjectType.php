<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ObjectType extends AbstractType
{
    use HasFieldsTrait;

    protected array $interfaces = [];

    public function addInterface(string $interface): static
    {
        $this->interfaces[$interface] = $interface;
        return $this;
    }

    public function getInterface(string $name): ?string
    {
        return $this->interfaces[$name] ?? null;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }
}
