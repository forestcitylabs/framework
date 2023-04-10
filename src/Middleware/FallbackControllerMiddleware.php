<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Utility\ParameterProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FallbackControllerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private string $controller,
        private string $method,
        private ContainerInterface $container,
        private ParameterProcessor $parameter_processor
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Pass to the next middleware.
        $response = $handler->handle($request);

        // For all 404 GET requests fall back on the controller.
        if (404 === $response->getStatusCode() && 'GET' === $request->getMethod()) {
            // Process parameters and get controller.
            $controller = $this->container->get($this->controller);
            $parameters = $this->parameter_processor->processParameters([$controller, $this->method]);

            // Return the response from the fallback controller.
            return call_user_func([$controller, $this->method], ...$parameters);
        }

        // Return the response.
        return $response;
    }
}
