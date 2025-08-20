<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Manager;

use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;

interface RefreshTokenManagerInterface
{
    public function generateRefreshToken(): RefreshTokenInterface;

    public function findRefreshTokenByToken(string $token): ?RefreshTokenInterface;

    public function persistRefreshToken(RefreshTokenInterface $refresh_token): void;

    public function revokeRefreshToken(RefreshTokenInterface $refresh_token): void;
}
