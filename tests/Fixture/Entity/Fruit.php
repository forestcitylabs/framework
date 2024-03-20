<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\InterfaceType]
abstract class Fruit
{
    #[GraphQL\Field]
    #[GraphQL\Argument]
    #[ORM\Column]
    protected RegionEnum $region;

    protected function getRegion(): RegionEnum
    {
        return $this->region;
    }

    public function setRegion(RegionEnum $region): static
    {
        $this->region = $region;
        return $this;
    }
}
