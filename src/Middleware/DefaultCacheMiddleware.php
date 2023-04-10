<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DefaultCacheMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $max_age = 86400
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // If the cache header is not set we will set a default.
        if (
            !$response->hasHeader('cache-control')
            && $response->getStatusCode() === 200
            && $request->getMethod() === 'GET'
        ) {
            $response = $response->withHeader('cache-control', 'max-age=' . $this->max_age);
        }

        return $response;
    }
}
