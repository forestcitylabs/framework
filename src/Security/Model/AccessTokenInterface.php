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

interface AccessTokenInterface
{
    public function getUser(): ?UserInterface;

    public function setUser(?UserInterface $user): void;

    public function getToken(): string;

    public function setToken(string $token): void;

    public function getScopes(): array;

    public function addScope(string $scope): void;

    public function hasScope(string $scope): bool;

    public function getExpiresAt(): DateTimeImmutable;

    public function setExpiresAt(DateTimeImmutable $expires_at): void;
}
