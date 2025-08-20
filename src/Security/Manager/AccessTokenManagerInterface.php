<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Manager;

use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;

interface AccessTokenManagerInterface
{
    public function generateAccessToken(): AccessTokenInterface;

    public function findAccessToken(string $token): ?AccessTokenInterface;

    public function revokeAccessToken(AccessTokenInterface $token): void;

    public function persistAccessToken(AccessTokenInterface $token): void;
}
