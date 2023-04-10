<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security\Attribute;

use Psr\Http\Message\ServerRequestInterface;

interface RequirementInterface
{
    /**
     * Check the requirement against a given request.
     *
     * @param  ServerRequestInterface $request The incoming request.
     * @throws HttpException                   If the requirement fails.
     */
    public function checkRequirement(ServerRequestInterface $request): void;
}
