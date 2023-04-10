<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security\Model;

interface UserInterface
{
    public function getIdentifier(): string;

    public function getPassword(): string;

    public function getRoles(): array;

    public function hasRole(string $role): bool;

    public function isActive(): bool;
}
