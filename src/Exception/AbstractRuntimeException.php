<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Exception;

use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;
use RuntimeException;

abstract class AbstractRuntimeException extends RuntimeException implements ClientAware, ProvidesExtensions
{
    abstract public function getCategory(): string;
    abstract public function getDetail(): string;

    public function getExtensions(): ?array
    {
        return [
            'category' => $this->getCategory(),
            'detail' => $this->getDetail(),
        ];
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
