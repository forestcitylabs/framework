<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;
use ForestCityLabs\Framework\Utility\SerializerTrait;

#[Attribute(Attribute::TARGET_CLASS)]
abstract class AbstractType
{
    use SerializerTrait;

    protected ?string $class_name = null;

    public function __construct(
        protected ?string $name = null,
        protected ?string $description = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->class_name;
    }

    public function setClassName(string $class_name): self
    {
        $this->class_name = $class_name;

        return $this;
    }
}
