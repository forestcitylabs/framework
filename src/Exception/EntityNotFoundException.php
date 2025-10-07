<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Exception;

class EntityNotFoundException extends AbstractRuntimeException
{
    public function getCategory(): string
    {
        return 'entity';
    }

    public function getDetail(): string
    {
        return 'not_found';
    }
}
