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
use ForestCityLabs\Framework\Routing\MetadataProvider;
use ForestCityLabs\Framework\Security\Exception\ForbiddenException;
use ForestCityLabs\Framework\Security\Exception\InsufficientScopeException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\RequirementChecker;
use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConversionException;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use LogicException;
use Psr\Container\ContainerInterface;
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
        private MetadataProvider $metadata_provider,
        private ResponseFactoryInterface $response_factory,
        private RequirementChecker $requirement_checker,
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

                // Check the requirements for this route.
                $this->requirement_checker->checkRequirements(
                    [$controller, $method_name],
                    $request
                );

                // Call the function.
                return call_user_func([$controller, $method_name], ...$parameters);
            } catch (UnauthorizedException) {
                // Caught an unauthorized error.
                return $this->response_factory->createResponse(401);
            } catch (InsufficientScopeException) {
                // Caught an insufficient scope error.
                return $this->response_factory->createResponse(403, 'Insufficient scope');
            } catch (ForbiddenException) {
                // Caught a forbidden error.
                return $this->response_factory->createResponse(403);
            } catch (ParameterConversionException) {
                // Caught a parameter conversion error.
                return $this->response_factory->createResponse(404);
            }
        }

        // We should never reach this point.
        throw new LogicException('Invalid dispatcher status.');
    }
}
