<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Utility\ParameterConverter;

use Exception;
use GraphQL\Error\ClientAware;

class ParameterConversionException extends Exception implements ClientAware
{
    public function isClientSafe()
    {
        return false;
    }

    public function getCategory()
    {
        return 'internal';
    }
}
