<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security\Model;

use DateTimeImmutable;

interface RefreshTokenInterface
{
    public function getUser(): UserInterface;

    public function getToken(): string;

    public function getScopes(): array;

    public function hasScope(string $scope): bool;

    public function getExpiry(): DateTimeImmutable;
}
