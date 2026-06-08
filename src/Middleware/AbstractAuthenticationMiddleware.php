<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Middleware;

use DateTimeImmutable;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

abstract class AbstractAuthenticationMiddleware implements MiddlewareInterface
{
    protected const TOKEN_ATTRIBUTE = '_access_token';

    public static function getTokenFromRequest(ServerRequestInterface $request): AccessTokenInterface
    {
        return $request->getAttribute(self::TOKEN_ATTRIBUTE);
    }

    protected function dispatchRequestWithToken(
        ServerRequestInterface $request,
        AccessTokenInterface $token
    ): ServerRequestInterface {
        if ($token->getExpiresAt() > (new DateTimeImmutable()) && $token->isActive()) {
            return $request->withAttribute(self::TOKEN_ATTRIBUTE, $token);
        }
        return $request;
    }
}
