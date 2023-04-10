<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security;

final class RoleRegistry
{
    public function __construct(
        private array $roles
    ) {
    }

    public function getAllRoles(): array
    {
        return array_keys($this->roles);
    }

    public function roleExists(string $role): bool
    {
        return array_key_exists($role, $this->roles);
    }

    public function filterPrivilegedRoles(array $roles): array
    {
        return array_filter($roles, function ($value) {
            return !$this->roles[$value];
        });
    }
}
