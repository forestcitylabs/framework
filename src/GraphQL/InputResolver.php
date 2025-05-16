<?php

namespace ForestCityLabs\Framework\GraphQL;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\GraphQL\Attribute\InputType;
use ForestCityLabs\Framework\GraphQL\Transformer\TransformerManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class InputResolver
{
    public function __construct(
        private PropertyAccessorInterface $property_accessor,
        private MetadataProvider $metadata_provider,
        private EntityManagerInterface $em,
        private TransformerManager $transformer_manager
    ) {
    }

    public function resolve(array $values, InputType $input, ?object $object = null): object
    {
        // Create the object if not specified.
        if (null === $object) {
            $object = new ($input->getClassName());
        }

        // Assign passed values.
        foreach ($input->getArguments() as $argument) {
            if (!isset($values[$argument->getName()]) || $values[$argument->getName()] === null) {
                continue;
            }

            // Resolve embedded inputs.
            if ($this->metadata_provider->getTypeMetadata($argument->getType()) instanceof InputType) {
                $values[$argument->getName()] = $this->resolve(
                    $values[$argument->getName()],
                    $this->metadata_provider->getTypeMetadata($argument->getType())
                );
            }

            // Check if there is a value transformer for this type.
            if (null !== $transformer = $this->transformer_manager->getTransformer($argument->getNativeType())) {
                $values[$argument->getName()] = $transformer->transformInput($values[$argument->getName()]);
            }

            // Use the property accessor to set the value.
            if (isset($values[$argument->getName()])) {
                $this->property_accessor->setValue(
                    $object,
                    $argument->getAttributeName(),
                    $values[$argument->getName()]
                );
            }
        }

        // Return the object.
        return $object;
    }
}
