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

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class ObjectField extends AbstractField
{
    public const TYPE_PROPERTY = 'property';
    public const TYPE_METHOD = 'method';

    /**
     * Whether this field targets a method or property.
     */
    protected string $field_type;

    /**
     * The arguments that will be used for this field.
     */
    protected array $arguments = [];

    public function getFieldType(): string
    {
        return $this->field_type;
    }

    public function setFieldType(string $field_type): self
    {
        $this->field_type = $field_type;

        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function addArgument(Argument $argument): self
    {
        $this->arguments[$argument->getName()] = $argument;

        return $this;
    }
}
