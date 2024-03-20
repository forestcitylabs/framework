<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

class HttpUnauthorizedMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        private Environment $twig,
        private string $redirect = '/login'
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Delegate to next handler, catch unauthorized exceptions.
        $response = $handler->handle($request);
        if (401 === $response->getStatusCode()) {
            $response = $response->withHeader('Location', $this->redirect)->withStatus(302);
        }

        // Return the response.
        return $response;
    }
}
