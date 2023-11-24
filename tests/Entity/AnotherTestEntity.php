<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute\ObjectField;
use ForestCityLabs\Framework\GraphQL\Attribute\ObjectType;

#[ObjectType]
class AnotherTestEntity
{
    #[ObjectField]
    private TestEntity $reference;

    public function getReference(): TestEntity
    {
        return $this->reference;
    }

    public function setReference(TestEntity $reference): self
    {
        $this->reference = $reference;
        return $this;
    }
}
