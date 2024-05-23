<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\NamedType;

interface TypeDiff
{
    public function getOldType(): NamedType;
    public function getNewType(): NamedType;
}
