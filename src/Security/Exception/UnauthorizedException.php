<?php

namespace ForestCityLabs\Framework\Security\Exception;

use Throwable;

class UnauthorizedException extends HttpException
{
    public function __construct(
        string $message = "Please login to access this resource.",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function getDetail(): string
    {
        return 'unauthorized';
    }
}
