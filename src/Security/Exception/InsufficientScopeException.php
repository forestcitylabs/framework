<?php

namespace ForestCityLabs\Framework\Security\Exception;

class InsufficientScopeException extends ForbiddenException
{
    public function getDetail(): string
    {
        return 'insufficient_scope';
    }
}
