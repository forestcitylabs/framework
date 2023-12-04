<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache\Exception;

use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends CacheException implements InvalidArgumentExceptionInterface
{
}
