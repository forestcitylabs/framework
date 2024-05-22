<?php

namespace ForestCityLabs\Framework\Tests\Fixture\Generated\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\EnumType]
enum AppleType
{
    #[GraphQL\Value]
    case MACINTOSH;

    #[GraphQL\Value]
    case ROYAL_GALA;
}
