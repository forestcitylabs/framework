<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\EnumType]
enum AppleTypeEnum: string
{
    #[GraphQL\Value]
    case Macintosh = "macintosh";

    #[GraphQL\Value]
    case Green_delicious = "green_delicious";
}
