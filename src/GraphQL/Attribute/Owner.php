<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Owner
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
