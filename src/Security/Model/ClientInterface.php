<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Model;

interface ClientInterface
{
    /**
     * Get the client ID.
     *
     * @return string The client ID.
     */
    public function getIdentifier(): string;

    /**
     * Get the client secret.
     *
     * @return string The client secret.
     */
    public function getSecret(): ?string;

    /**
     * Get the redirect URI for the client.
     *
     * @return string The redirect URI.
     */
    public function getRedirectUris(): array;

    /**
     * Get the scopes allowed for the client.
     *
     * @return array<string> The allowed scopes.
     */
    public function getScopes(): array;

    public function hasRedirectUri(string $redirectUri): bool;

    public function isConfidential(): bool;
}
