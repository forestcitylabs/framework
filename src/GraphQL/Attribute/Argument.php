<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Argument
{
    private string $parameter_name;

    public function __construct(
        private ?string $name = null,
        private ?string $type = null,
        private ?string $description = null,
        protected ?bool $list = null,
        private ?bool $not_null = null
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getParameterName(): string
    {
        return $this->parameter_name;
    }

    public function setParameterName(string $parameter_name): self
    {
        $this->parameter_name = $parameter_name;

        return $this;
    }
}
