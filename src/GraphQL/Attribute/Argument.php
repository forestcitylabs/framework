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

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Argument extends AbstractType
{
    use HasTypeTrait;

    public const TYPE_PROPERTY = 'property';
    public const TYPE_PARAMETER = 'parameter';

    protected string $attribute_type;
    protected string $attribute_name;

    private mixed $default = null;

    public function __construct(
        ?string $name = null,
        ?string $description = null,
        ?string $type = null,
        ?bool $list = null,
        ?bool $not_null = null,
        mixed $default = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->list = $list;
        $this->not_null = $not_null;
        $this->default = $default;
    }

    public function setAttributeType(string $type): static
    {
        $this->attribute_type = $type;
        return $this;
    }

    public function getAttributeType(): string
    {
        return $this->attribute_type;
    }

    public function setAttributeName(string $name): static
    {
        $this->attribute_name = $name;
        return $this;
    }

    public function getAttributeName(): string
    {
        return $this->attribute_name;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function setDefault(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }
}
