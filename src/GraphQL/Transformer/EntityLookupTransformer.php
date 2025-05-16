<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class EntityLookupTransformer implements TransformerInterface
{
    public function __construct(
        private string $native_type,
        private EntityManagerInterface $em,
        private PropertyAccessorInterface $property_accessor
    ) {
    }

    public function transformOutput(mixed $value): mixed
    {
        return $this->property_accessor->getValue($value, 'id');
    }

    public function transformInput(mixed $value): mixed
    {
        return $this->em->getRepository($this->native_type)->findOneBy(['id' => $value]);
    }

    public function getNativeType(): string
    {
        return $this->native_type;
    }

    public function getGraphQLType(): string
    {
        return 'ID';
    }
}
