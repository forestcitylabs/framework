<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Routing\Attribute;

use Attribute;
use ForestCityLabs\Framework\Utility\SerializerTrait;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    use SerializerTrait;

    private ?string $class_name = null;
    private ?string $method_name = null;
    private ?RoutePrefix $prefix = null;

    public function __construct(
        private string $path,
        private array $methods = ['GET'],
        private ?string $name = null
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethods(): array
    {
        return $this->methods;
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

    public function getClassName(): ?string
    {
        return $this->class_name;
    }

    public function setClassName(string $class_name): self
    {
        $this->class_name = $class_name;

        return $this;
    }

    public function getMethodName(): ?string
    {
        return $this->method_name;
    }

    public function setMethodName(string $method_name): self
    {
        $this->method_name = $method_name;

        return $this;
    }

    public function getPrefix(): ?RoutePrefix
    {
        return $this->prefix;
    }

    public function setPrefix(?RoutePrefix $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }
}
