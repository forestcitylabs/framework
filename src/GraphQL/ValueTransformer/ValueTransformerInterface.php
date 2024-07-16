<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

interface ValueTransformerInterface
{
    /**
     * @deprecated
     */
    public function transformOutput(mixed $value): mixed;
}
