<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Transformer;

use Doctrine\ORM\EntityManagerInterface;

class EntityLookupTransformer implements TransformerInterface
{
    public function __construct(
        private string $native_type,
        private EntityManagerInterface $em
    ) {
    }

    public function transformOutput(mixed $value): mixed
    {
        return $value;
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
