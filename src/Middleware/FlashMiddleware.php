<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Session\Flash;
use ForestCityLabs\Framework\Session\Session;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FlashMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Ensure we have a session.
        if (null === $session = Session::fromRequest($request)) {
            throw new LogicException('Session middleware is required for flash middlewares.');
        }

        // Check if there's a flash in the session already.
        if ($session->hasValue('_flash')) {
            $flash = $session->getValue('_flash');
            assert($flash instanceof Flash);
        } else {
            $flash = new Flash();
        }

        // Delegate the response with our flash.
        $response = $handler->handle($request->withAttribute('_flash', $flash));

        // Add flash to session if not empty.
        if (!$flash->isEmpty()) {
            $session->setValue('_flash', $flash);
        } elseif ($session->hasValue('_flash')) {
            $session->removeValue('_flash');
        }

        // Return the response.
        return $response;
    }
}
