<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use DateTimeImmutable;
use Dflydev\FigCookies\Cookies;
use Dflydev\FigCookies\SetCookie;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Exception\UuidExceptionInterface;
use Ramsey\Uuid\Uuid;

class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionDriverInterface $session_driver
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Grab the cookies from the request.
        $cookies = Cookies::fromRequest($request);

        // Check if we have a session cookie.
        if ($cookies->has('_session')) {
            // Attempt to load an existing session.
            try {
                $id = Uuid::fromString($cookies->get('_session')->getValue());
                if (null === $session = $this->session_driver->load($id)) {
                    // Create a new session.
                    $session = (new Session(Uuid::uuid4()))
                        ->setExpiry(new DateTimeImmutable('+1 day'));
                }
            } catch (UuidExceptionInterface) {
                $session = (new Session(Uuid::uuid4()))
                    ->setExpiry(new DateTimeImmutable('+1 day'));
            }
        } else {
            // No session cookie, create a session.
            $session = (new Session(Uuid::uuid4()))
                ->setExpiry(new DateTimeImmutable('+1 day'));
        }

        // Check if the session is expired.
        if ((new DateTimeImmutable()) > $session->getExpiry()) {
            $this->session_driver->delete($session);
            $session = (new Session(Uuid::uuid4()))
                ->setExpiry(new DateTimeImmutable('+1 day'));
        }

        // Delegate request, adding session attribute.
        $response = $handler->handle($request->withAttribute('_session', $session));

        // If the session is empty remove it or ignore.
        if ($session->isEmpty()) {
            // Expire existing cookie.
            if ($cookies->has('_session')) {
                return $response
                    ->withAddedHeader('set-cookie', (string) SetCookie::create('_session')->expire())
                    ->withHeader('cache-control', 'no-store, no-cache, must-revalidate');
            }

            // Return the unaltered response.
            return $response;
        }

        // Persist the session.
        $this->session_driver->save($session);

        // If the session is new create a new session cookie.
        if (
            !$cookies->has('_session')
            || $cookies->get('_session')->getValue() !== (string) $session->getId()
        ) {
            $response = $response->withAddedHeader(
                'set-cookie',
                (string) SetCookie::create('_session')
                    ->withValue((string) $session->getId())
                    ->withPath('/')
                    ->withSecure()
                    ->withHttpOnly()
            );
        }

        // Return the response.
        return $response->withHeader('cache-control', 'no-store, no-cache, must-revalidate');
    }
}
