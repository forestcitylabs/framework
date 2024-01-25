<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class InterfaceType extends AbstractType
{
    use HasFieldsTrait;
}
