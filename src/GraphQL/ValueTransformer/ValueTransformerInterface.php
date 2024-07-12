<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

interface ValueTransformerInterface
{
    public function transformOutput(mixed $value): mixed;
    public function transformInput(mixed $value): mixed;
    public function getNativeType(): string;
    public function getGraphQLType(): string;
}
