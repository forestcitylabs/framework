<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Transformer;

class TransformerManager
{
    /**
     * @param array<TransformerInterface> $transformers
     */
    public function __construct(
        private array $transformers
    ) {
    }

    public function getTransformer(?string $native_type): ?TransformerInterface
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
