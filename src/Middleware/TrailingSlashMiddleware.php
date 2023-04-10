<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to redirect 404s based on trailing slashes.
 */
class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ResponseFactoryInterface $response_factory
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        if (404 === $response->getStatusCode()) {
            // For all get requests check the trailing slash.
            if ('GET' === $request->getMethod()) {
                $path = $request->getUri()->getPath();
                if ('/' == mb_substr($path, -1)) {
                    $new_path = mb_substr($path, 0, mb_strlen($path) - 1);
                } else {
                    $new_path = $path . '/';
                }

                // If the new route was found return a 301.
                $new_route = $this->dispatcher->dispatch($request->getMethod(), $new_path);
                if (Dispatcher::FOUND === $new_route[0]) {
                    return $this
                        ->response_factory
                        ->createResponse(301)
                        ->withHeader('location', $new_path);
                }
            }
        }

        return $response;
    }
}
