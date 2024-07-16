<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Transformer;

interface TransformerInterface
{
    public function transformOutput(mixed $value): mixed;
    public function transformInput(mixed $value): mixed;
    public function getNativeType(): string;
    public function getGraphQLType(): string;
}
