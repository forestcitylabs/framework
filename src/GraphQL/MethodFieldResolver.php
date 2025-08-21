<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL;

use ForestCityLabs\Framework\Events\PreGraphQLFieldResolveEvent;
use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Transformer\TransformerManager;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

class MethodFieldResolver implements FieldResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ParameterProcessor $parameter_processor,
        private TransformerManager $transformer_manager,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function resolveField(
        Field $field,
        ?object $object = null,
        array $args = [],
        ?ServerRequestInterface $request = null
    ): mixed {
        // Get the service and method strings.
        list($service_name, $method) = explode('::', $field->getAttributeName());

        // If the object has a method that is callable use that.
        if (is_callable([$object, $method])) {
            $service = $object;
        } else {
            $service = $this->container->get($service_name);
        }

        // Resolve the arguments.
        $args = $this->parameter_processor->processParameters(
            [$service, $method],
            $args + [$request, $object]
        );

        // Dispatch a pre-resolve event before continuing.
        $this->dispatcher->dispatch(new PreGraphQLFieldResolveEvent([$service, $method], $request, $args));

        // Call the function.
        $value = call_user_func([$service, $method], ...$args);

        // Check if there is a transformer for this type.
        if (null !== $transformer = $this->transformer_manager->getTransformer($field->getNativeType())) {
            $value = $transformer->transformOutput($value);
        }

        return $value;
    }
}
