<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Manager;

use ForestCityLabs\Framework\Security\Model\ClientInterface;

interface ClientManagerInterface
{
    public function findClientById(string $clientId): ?ClientInterface;
    public function validateClientCredentials(string $clientId, string $clientSecret): bool;
}
