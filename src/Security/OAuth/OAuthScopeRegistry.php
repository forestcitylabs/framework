<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth;

class OAuthScopeRegistry
{
    public function __construct(
        private array $scopes
    ) {
    }

    public function getAllScopes(): array
    {
        return array_keys($this->scopes);
    }

    public function filterPrivilegedScopes(array $scopes): array
    {
        return array_values(array_filter($scopes, function ($value) {
            if (isset($this->scopes[$value])) {
                return !$this->scopes[$value];
            }
            return true;
        }));
    }

    public function isValidScope(string $scope): bool
    {
        return array_key_exists($scope, $this->scopes);
    }
}
