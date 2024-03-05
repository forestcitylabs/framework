<?php

namespace ForestCityLabs\Framework\Security\Exception;

class ForbiddenException extends HttpException
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory()
    {
        return 'security';
    }
}
