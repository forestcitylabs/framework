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
        foreach ($input->getFields() as $field) {
            if ($values[$field->getName()] === null) {
                continue;
            }

            // Resolve embedded inputs.
            if ($this->metadata_provider->getTypeMetadata($field->getType()) instanceof InputType) {
                $values[$field->getName()] = $this->resolve(
                    $values[$field->getName()],
                    $this->metadata_provider->getTypeMetadata($field->getType())
                );
            }

            // Lookup objects by ID.
            if ($field->getType() === 'ID') {
                $property = new ReflectionProperty($input->getClassName(), $field->getData());
                $repo = $this->em->getRepository($property->getType()->getName());
                if (null === $entity = $repo->findOneBy(['id' => $values[$field->getName()]])) {
                    throw new EntityNotFoundException();
                }

                // Set the value to an entity.
                $values[$field->getName()] = $entity;
            }

            if (isset($values[$field->getName()])) {
                $this->property_accessor->setValue($object, $field->getData(), $values[$field->getName()]);
            }
        }

        // Return the object.
        return $object;
    }
}
