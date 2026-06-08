<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class SessionAuthenticationMiddleware extends AbstractAuthenticationMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get the session.
        if (null !== $session = SessionMiddleware::getSessionFromRequest($request)) {
            // Get the token from the session.
            if (null !== $token = $session->getValue(self::TOKEN_ATTRIBUTE)) {
                return $handler->handle($this->dispatchRequestWithToken($request, $token));
            }
        }

        return $handler->handle($request);
    }
}
