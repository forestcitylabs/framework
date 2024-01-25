<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Attribute;

trait HasTypeTrait
{
    protected ?string $type;
    protected ?bool $list;
    protected ?bool $not_null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getList(): ?bool
    {
        return $this->list;
    }

    public function setList(bool $list): static
    {
        $this->list = $list;
        return $this;
    }

    public function getNotNull(): ?bool
    {
        return $this->not_null;
    }

    public function setNotNull(bool $not_null): static
    {
        $this->not_null = $not_null;
        return $this;
    }
}
