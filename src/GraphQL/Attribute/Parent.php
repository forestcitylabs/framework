<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Parent
{
    function __construct(private string $parent)
    {
    }

    public function getParent(): string
    {
        return $this->parent;
    }
}
