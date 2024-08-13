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
        @trigger_error(sprintf('Method %s is deprecated and will be removed in version 2.0.0.', __FUNCTION__), E_USER_DEPRECATED);
        foreach ($this->transformers as $transformer) {
            $value = $transformer->transformOutput($value);
        }
        return $value;
    }
}
