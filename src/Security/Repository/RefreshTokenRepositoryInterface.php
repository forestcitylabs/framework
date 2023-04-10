<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security\Repository;

use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;

interface RefreshTokenRepositoryInterface
{
    public function findExpiredTokens(): iterable;

    public function findToken(string $token): ?RefreshTokenInterface;
}
