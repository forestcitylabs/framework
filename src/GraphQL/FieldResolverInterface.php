<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\ObjectField;
use Psr\Http\Message\ServerRequestInterface;

interface FieldResolverInterface
{
    public function resolveField(
        ObjectField $field,
        ?object $object = null,
        array $args = [],
        ServerRequestInterface $request = null
    ): mixed;
}
