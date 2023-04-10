<?php

namespace ForestCityLabs\Framework\Security\Exception;

class ForbiddenException extends HttpException
{
    public function isClientSafe()
    {
        return true;
    }

    public function getCategory()
    {
        return 'security';
    }
}
