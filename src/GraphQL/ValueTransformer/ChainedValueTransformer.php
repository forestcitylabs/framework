<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

class ChainedValueTransformer implements ValueTransformerInterface
{
    public function __construct(
        private array $transformers
    ) {
    }
    public function transformOutput(mixed $value): mixed
    {
        foreach ($this->transformers as $transformer) {
            $value = $transformer->transformOutput($value);
        }
        return $value;
    }
}
