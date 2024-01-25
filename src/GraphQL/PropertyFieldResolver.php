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
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class PropertyFieldResolver implements FieldResolverInterface
{
    public function __construct(
        private PropertyAccessorInterface $property_accessor,
        private ValueTransformerInterface $value_transformer
    ) {
    }

    public function resolveField(
        Field $field,
        ?object $object = null,
        array $args = [],
        ServerRequestInterface $request = null
    ): mixed {
        return $this->value_transformer->transformOutput(
            $this->property_accessor->getValue($object, $field->getAttributeName())
        );
    }
}
