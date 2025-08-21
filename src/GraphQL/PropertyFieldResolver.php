<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\Transformer\TransformerManager;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PropertyFieldResolver implements FieldResolverInterface
{
    public function __construct(
        private PropertyAccessorInterface $property_accessor,
        private TransformerManager $transformer_manager
    ) {
    }

    public function resolveField(
        Field $field,
        ?object $object = null,
        array $args = [],
        ?ServerRequestInterface $request = null
    ): mixed {
        // Get the value from the object.
        $value = $this->property_accessor->getValue($object, $field->getAttributeName());

        // Check if there is a value transformer for this type.
        if (null !== $transformer = $this->transformer_manager->getTransformer($field->getNativeType())) {
            $value = $transformer->transformOutput($value);
        }

        return $value;
    }
}
