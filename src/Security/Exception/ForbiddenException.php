<?php

namespace ForestCityLabs\Framework\Security\Exception;

class ForbiddenException extends HttpException
{
    public function getCategory(): string
    {
        return 'security';
    }

    public function getDetail(): string
    {
        return 'forbidden';
    }
}
