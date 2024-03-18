<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Miscellaneous;

class ParameterConverterNegatives
{
    public function cantConvert(
        $untyped,
        int $builtin,
        int|bool $untion
    ): void {
    }
}
