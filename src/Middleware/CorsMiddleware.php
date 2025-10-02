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
use Psr\Log\LoggerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private LoggerInterface $logger,
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
            $this->logger->debug('CORS: No origin header present, passing through');
            return $handler->handle($request);
        }

        // Attempt to map an origin.
        if (null === $allowed_origin = $this->allowedOrigin($request)) {
            $origin = $request->getHeader('origin')[0];
            $this->logger->warning('CORS: Origin not allowed', [
                'origin' => $origin,
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri()
            ]);
            return $this->response_factory->createResponse(403);
        }

        // If this is a pre-flight request return all headers immediately.
        if ($request->getMethod() == "OPTIONS") {
            $this->logger->info('CORS: Handling preflight request', [
                'origin' => $allowed_origin,
                'uri' => (string) $request->getUri()
            ]);
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
            $this->logger->warning('CORS: Method not allowed', [
                'method' => $request->getMethod(),
                'allowed_methods' => $this->allow_methods,
                'origin' => $allowed_origin,
                'uri' => (string) $request->getUri()
            ]);
            return $this->response_factory->createResponse(403);
        }

        $this->logger->debug('CORS: Request allowed', [
            'origin' => $allowed_origin,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri()
        ]);

        // Allow request to continue with cross-origin header.
        return $handler
            ->handle($request)
            ->withHeader('access-control-allow-origin', $allowed_origin);
    }

    private function allowedOrigin(ServerRequestInterface $request): ?string
    {
        $origin = $request->getHeader('origin')[0];

        // Check if this is a same-origin request
        $request_origin = $this->getRequestOrigin($request);
        if ($origin === $request_origin) {
            return $origin;
        }

        foreach ($this->allow_origins as $allowed_origin) {
            if ($allowed_origin == "*") {
                return "*";
            } elseif ($allowed_origin == $origin) {
                return $origin;
            }
        }
        return null;
    }

    private function getRequestOrigin(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();

        $origin = $scheme . '://' . $host;

        // Only include port if it's not the default port for the scheme
        if (
            ($scheme === 'http' && $port !== null && $port !== 80) ||
            ($scheme === 'https' && $port !== null && $port !== 443)
        ) {
            $origin .= ':' . $port;
        }

        return $origin;
    }
}
