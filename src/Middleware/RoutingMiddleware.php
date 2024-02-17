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
use ForestCityLabs\Framework\Events\PreRouteDispatchEvent;
use ForestCityLabs\Framework\Routing\MetadataProvider;
use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConversionException;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container,
        private Dispatcher $dispatcher,
        private EventDispatcherInterface $event_dispatcher,
        private MetadataProvider $metadata_provider,
        private ResponseFactoryInterface $response_factory,
        private ParameterProcessor $parameter_processor
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Match the route.
        [$status, $route, $arguments] = array_pad($this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        ), 3, null);

        // If not found this handler will return a plain 404.
        if (Dispatcher::NOT_FOUND === $status) {
            return $this->response_factory->createResponse(404);
        }

        // Method not allowed returns a plain 405.
        if (Dispatcher::METHOD_NOT_ALLOWED == $status) {
            return $this->response_factory->createResponse(405);
        }

        // Route matches.
        if (Dispatcher::FOUND === $status) {
            // Get class name and method.
            list($class_name, $method_name) = $route;

            // Get the controller from the container.
            $controller = $this->container->get($class_name);

            try {
                // Process parameters.
                $parameters = $this->parameter_processor->processParameters(
                    [$controller, $method_name],
                    $arguments + [$request]
                );
            } catch (ParameterConversionException) {
                // Caught a parameter conversion error.
                return $this->response_factory->createResponse(404);
            }
        }

        // Dispatch an event before allowing request.
        $event = new PreRouteDispatchEvent($controller, $method_name, $request);
        $this->event_dispatcher->dispatch($event);

        // If the event has a response attached we should return that.
        if (null !== $response = $event->getResponse()) {
            return $response;
        }

        // Call the function.
        return call_user_func([$controller, $method_name], ...$parameters);
    }
}
