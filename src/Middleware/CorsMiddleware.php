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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private array $allow_origins = [],
        private array $allow_headers = [],
        private array $allow_methods = [],
        private ?int $max_age = 3600
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // If there is no origin we can't process.
        if (!$request->hasHeader('origin')) {
            return $handler->handle($request);
        }

        // Attempt to map an origin.
        if (null === $allowed_origin = $this->allowedOrigin($request)) {
            return $this->response_factory->createResponse(403);
        }

        // If this is a pre-flight request return all headers immediately.
        if ($request->getMethod() == "OPTIONS") {
            $response = $this
                ->response_factory
                ->createResponse(204)
                ->withHeader('access-control-allow-origin', $allowed_origin);
            if (count($this->allow_headers) > 0) {
                $response = $response->withHeader('access-control-allow-headers', implode(", ", $this->allow_headers));
            }
            if (count($this->allow_methods) > 0) {
                $response = $response->withHeader('access-control-allow-methods', implode(", ", $this->allow_methods));
            }
            if ($this->max_age !== null) {
                $response = $response->withHeader('access-control-max-age', $this->max_age);
            }
            return $response;
        }

        // Ensure we don't violate allowed methods.
        if (count($this->allow_methods) > 0 && !in_array($request->getMethod(), $this->allow_methods)) {
            return $this->response_factory->createResponse(403);
        }

        // Allow request to continue with cross-origin header.
        return $handler
            ->handle($request)
            ->withHeader('access-control-allow-origin', $allowed_origin);
    }

    private function allowedOrigin(ServerRequestInterface $request): ?string
    {
        $origin = $request->getHeader('origin')[0];
        foreach ($this->allow_origins as $allowed_origin) {
            if ($allowed_origin == "*") {
                return "*";
            } elseif ($allowed_origin == $origin) {
                return $origin;
            }
        }
        return null;
    }
}
