<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Transformer;

use DateTimeImmutable;

class DateTimeImmutableTransformer implements TransformerInterface
{
    public function transformOutput(mixed $value): mixed
    {
        return $value->format('c');
    }

    public function transformInput(mixed $value): mixed
    {
        return new DateTimeImmutable($value);
    }

    public function getNativeType(): string
    {
        return DateTimeImmutable::class;
    }

    public function getGraphQLType(): string
    {
        return 'String';
    }
}
