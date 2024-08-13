<?php

namespace ForestCityLabs\Framework\GraphQL\ValueTransformer;

use DateTimeInterface;

class DateTimeValueTransformer implements ValueTransformerInterface
{
    public function transformOutput(mixed $value): mixed
    {
        @trigger_error(sprintf('Method %s is deprecated and will be removed in version 2.0.0.', __FUNCTION__), E_USER_DEPRECATED);
        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        return $value;
    }
}
