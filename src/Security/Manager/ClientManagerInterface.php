<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Manager;

use ForestCityLabs\Framework\Security\Model\ClientInterface;

interface ClientManagerInterface
{
    public function findClientById(string $clientId): ?ClientInterface;

    /**
     * Validate the client credentials.
     *
     * @param string $clientId The client ID.
     * @param string $clientSecret The client secret.
     * @return bool True if the credentials are valid, false otherwise.
     */
    public function validateClientCredentials(string $clientId, string $clientSecret): bool;

    /**
     * Get the redirect URI for a given client.
     *
     * @param string $clientId The client ID.
     * @return string|null The redirect URI or null if not found.
     */
    public function getRedirectUri(string $clientId): ?string;
}
