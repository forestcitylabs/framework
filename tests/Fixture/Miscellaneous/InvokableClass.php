<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Miscellaneous;

class InvokableClass
{
    public function __invoke()
    {
        return 'invoked';
    }
}
