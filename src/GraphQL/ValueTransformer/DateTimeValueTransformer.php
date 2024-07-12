<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

use DateTimeImmutable;

class DateTimeValueTransformer implements ValueTransformerInterface
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
