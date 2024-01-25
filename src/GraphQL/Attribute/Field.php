<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class Field extends AbstractType
{
    use HasTypeTrait;
    use HasArgumentsTrait;
    use IsDeprecableTrait;

    public const TYPE_METHOD = 'method';
    public const TYPE_PROPERTY = 'property';

    protected string $attribute_type;
    protected string $attribute_name;

    public function __construct(
        ?string $name = null,
        ?string $description = null,
        ?string $type = null,
        ?bool $list = null,
        ?bool $not_null = null,
        ?string $deprecation_reason = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->list = $list;
        $this->not_null = $not_null;
        $this->deprecation_reason = $deprecation_reason;
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
}
