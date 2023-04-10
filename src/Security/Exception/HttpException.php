<?php

namespace ForestCityLabs\Framework\Security\Exception;

use Exception;
use GraphQL\Error\ClientAware;

abstract class HttpException extends Exception implements ClientAware
{
}
