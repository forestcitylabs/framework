<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

use DateTimeInterface;

class DateTimeValueTransformer implements ValueTransformerInterface
{
    public function transformOutput(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        return $value;
    }
}
