<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use ForestCityLabs\Framework\Session\Session;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private const SESSION_ATTRIBUTE = '_session';

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Grab the cookies from the request.
        $cookies = Cookies::fromRequest($request);

        // Check if there's a session in the cookies.
        if ($cookies->has(session_name()) && session_status() !== PHP_SESSION_ACTIVE) {
            // Start the session and close it immediately to avoid session locking.
            session_start(['read_and_close' => true]);

            // Hydrate the session with existing session data.
            $session = new Session($_SESSION);
        } else {
            // Create a blank session.
            $session = new Session();
        }

        // Dispatch the request with the session attached.
        $response = $handler->handle($request->withAttribute(self::SESSION_ATTRIBUTE, $session));

        // If the session is dirty we need to either persist or destroy it.
        if ($session->isDirty()) {
            if ($session->isEmpty() && $cookies->has(session_name())) {
                // If the session is empty destroy it and clear the cookie.
                session_start();
                session_destroy();
                $response = $response->withAddedHeader(
                    'set-cookie',
                    (string) SetCookie::create(session_name())->expire()
                )
                    ->withHeader('cache-control', 'no-store, no-cache, must-revalidate');
            }

            // Session is not empty, persist it.
            if (!$session->isEmpty()) {
                session_start();
                $_SESSION = $session->getData();
                session_write_close();
            }
        }

        // Return the response.
        return $response;
    }

    public static function getSessionFromRequest(ServerRequestInterface $request): Session
    {
        if (!$request->hasAttribute(self::SESSION_ATTRIBUTE)) {
            throw new LogicException('No session found!');
        }

        return $request->getAttribute(self::SESSION_ATTRIBUTE);
    }
}
