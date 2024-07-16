<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

class ValueTransformerManager
{
    /**
     * @param array<ValueTransformerInterface> $transformers
     */
    public function __construct(
        private array $transformers
    ) {
    }

    public function getTransformer(?string $native_type): ?ValueTransformerInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->getNativeType() === $native_type) {
                return $transformer;
            }
        }

        return null;
    }

    public function getGraphQLType(?string $native_type): ?string
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->getNativeType() == $native_type) {
                return $transformer->getGraphQLType();
            }
        }

        return null;
    }
}
