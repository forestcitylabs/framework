<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security;

use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;

interface AccessTokenManagerInterface
{
    public function generateAccessToken(UserInterface $user, array $scopes): AccessTokenInterface;

    public function generateRefreshToken(AccessTokenInterface $access_token): ?RefreshTokenInterface;

    public function exchangeRefreshToken(RefreshTokenInterface $refresh_token): ?AccessTokenInterface;
}
