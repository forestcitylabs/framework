<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute\EnumType;
use ForestCityLabs\Framework\GraphQL\Attribute\Value;

#[EnumType]
enum RegionEnum
{
    #[Value]
    case NORTH;

    #[Value]
    case EQUATOR;

    #[Value]
    case SOUTH;
}
