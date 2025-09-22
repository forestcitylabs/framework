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
        private array $roles,
        private array $hierarchy = [],
    ) {
    }

    public function getAllRoles(): array
    {
        return $this->roles;
    }

    public function roleExists(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function getRolesUnder(string $role): array
    {
        $childRoles = [];

        // Check if this role has direct children in the hierarchy
        if (isset($this->hierarchy[$role])) {
            foreach ($this->hierarchy[$role] as $childRole) {
                // Add the direct child
                $childRoles[] = $childRole;

                // Recursively get all descendants of this child
                $grandChildren = $this->getRolesUnder($childRole);
                $childRoles = array_merge($childRoles, $grandChildren);
            }
        }

        return array_unique($childRoles);
    }
}
