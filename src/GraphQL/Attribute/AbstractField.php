<?php

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use ForestCityLabs\Framework\Utility\SerializerTrait;

abstract class AbstractField
{
    use SerializerTrait;

    /**
     * The method or property data.
     */
    protected string|array $data;

    public function __construct(
        protected ?string $name = null,
        protected ?string $description = null,
        protected ?string $type = null,
        protected ?bool $list = null,
        protected ?bool $not_null = null
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

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getList(): ?bool
    {
        return $this->list;
    }

    public function setList(bool $list): self
    {
        $this->list = $list;

        return $this;
    }

    public function getNotNull(): ?bool
    {
        return $this->not_null;
    }

    public function setNotNull(bool $not_null): self
    {
        $this->not_null = $not_null;

        return $this;
    }

    public function getData(): string|array
    {
        return $this->data;
    }

    public function setData(string|array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
