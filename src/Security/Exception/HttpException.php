<?php

namespace ForestCityLabs\Framework\Security\Exception;

use ForestCityLabs\Framework\Exception\AbstractRuntimeException;

abstract class HttpException extends AbstractRuntimeException
{
    public function getCategory(): string
    {
        return 'http';
    }

    public function getExtensions(): ?array
    {
        return [
            'category' => $this->getCategory(),
            'detail' => $this->getDetail(),
        ];
    }
}
