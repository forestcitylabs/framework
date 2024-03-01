<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Security;

final class ScopeRegistry
{
    public const ALL = 'all';

    public function __construct(
        private array $scopes
    ) {
    }

    public function getAllScopes(): array
    {
        return array_keys($this->scopes);
    }

    public function scopeExists(string $scope): bool
    {
        return self::ALL === $scope || array_key_exists($scope, $this->scopes);
    }

    public function filterPrivilegedScopes(array $scopes): array
    {
        // If this contains the "all" scope it is actually ALL scopes.
        if (in_array(self::ALL, $this->scopes)) {
            $scopes = $this->getAllScopes();
        }

        return array_values(array_filter($scopes, function ($value) {
            return !$this->scopes[$value];
        }));
    }
}
