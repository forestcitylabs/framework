<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc;

use InvalidArgumentException;

class OidcClaimRegistry
{
    public function __construct(
        private array $claims = [],
        private array $groups = [],
    ) {
        // Validate the groups only contain defined claims.
        foreach ($this->groups as $group => $gclaims) {
            foreach ($gclaims as $claim) {
                if (!in_array($claim, array_keys($claims), true)) {
                    throw new InvalidArgumentException(
                        sprintf('Claim "%s" in group "%s" is not defined.', $claim, $group)
                    );
                }
            }
        }
    }

    public function getClaims(): array
    {
        return $this->claims;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroup($group): array
    {
        if (!array_key_exists($group, $this->groups)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist.', $group));
        }

        return $this->groups[$group];
    }

    public function filterPrivilegedClaims(array $claims): array
    {
        // Filter the claims based on the defined claims.
        return array_values(array_filter($claims, function ($claim) {
            return !$this->claims[$claim];
        }));
    }

    public function isValidClaim(string $claim): bool
    {
        // Check if the claim is defined in the registry.
        return array_key_exists($claim, $this->claims);
    }

    public function isValidGroup(string $group): bool
    {
        // Check if the group is defined in the registry.
        return array_key_exists($group, $this->groups);
    }
}
