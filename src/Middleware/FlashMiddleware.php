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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FlashMiddleware implements MiddlewareInterface
{
    private const FLASH_ATTRIBUTE = '_flash';

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Ensure we have a session.
        $session = SessionMiddleware::getSessionFromRequest($request);

        // Check if there's a flash in the session already.
        if ($session->hasValue(self::FLASH_ATTRIBUTE)) {
            $flash = $session->getValue(self::FLASH_ATTRIBUTE);
            assert($flash instanceof Flash);
        } else {
            $flash = new Flash();
        }

        // Delegate the response with our flash.
        $response = $handler->handle($request->withAttribute(self::FLASH_ATTRIBUTE, $flash));

        // Add flash to session if not empty.
        if (!$flash->isEmpty()) {
            $session->setValue(self::FLASH_ATTRIBUTE, $flash);
        } elseif ($session->hasValue(self::FLASH_ATTRIBUTE)) {
            $session->removeValue(self::FLASH_ATTRIBUTE);
        }

        // Return the response.
        return $response;
    }

    public static function getFlashFromRequest(ServerRequestInterface $request): Flash
    {
        return $request->getAttribute(self::FLASH_ATTRIBUTE);
    }
}
