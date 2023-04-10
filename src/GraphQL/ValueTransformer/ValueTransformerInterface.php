<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

interface ValueTransformerInterface
{
    public function transformOutput(mixed $value): mixed;
}
