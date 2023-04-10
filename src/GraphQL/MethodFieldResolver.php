<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\ObjectField;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class MethodFieldResolver implements FieldResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ParameterProcessor $parameter_processor,
        private ValueTransformerInterface $value_transformer
    ) {
    }

    public function resolveField(
        ObjectField $field,
        ?object $object = null,
        array $args = [],
        ServerRequestInterface $request = null
    ): mixed {
        // If the object is passed use that, otherwise use a service.
        if (null !== $object) {
            list(, $method) = $field->getData();
        } else {
            list($service, $method) = $field->getData();
            $object = $this->container->get($service);
        }

        // Resolve the arguments.
        $args = $this->parameter_processor->processParameters(
            [$object, $method],
            $args + [$request]
        );

        // Call the function.
        return $this->value_transformer->transformOutput(
            call_user_func([$object, $method], ...$args)
        );
    }
}
