<?php

namespace ForestCityLabs\Framework\GraphQL;

use Application\Exception\GraphQL\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\GraphQL\Attribute\InputType;
use ReflectionProperty;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class InputResolver
{
    public function __construct(
        private PropertyAccessorInterface $property_accessor,
        private MetadataProvider $metadata_provider,
        private EntityManagerInterface $em
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

            // Lookup objects by ID.
            if ($argument->getType() === 'ID') {
                $property = new ReflectionProperty($input->getClassName(), $argument->getAttributeName());
                $repo = $this->em->getRepository($property->getType()->getName());
                if (null === $entity = $repo->findOneBy(['id' => $values[$argument->getName()]])) {
                    throw new EntityNotFoundException();
                }

                // Set the value to an entity.
                $values[$argument->getName()] = $entity;
            }

            if (isset($values[$argument->getName()])) {
                $this->property_accessor->setValue($object, $argument->getAttributeName(), $values[$argument->getName()]);
            }
        }

        // Return the object.
        return $object;
    }
}
