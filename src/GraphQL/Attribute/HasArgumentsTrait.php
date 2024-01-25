<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Attribute;

trait HasArgumentsTrait
{
    protected array $arguments = [];

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function addArgument(Argument $argument): static
    {
        $this->arguments[$argument->getName()] = $argument;
        return $this;
    }

    public function getArgument(string $name): ?Argument
    {
        return $this->arguments[$name] ?? null;
    }
}
