<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Manager;

use ForestCityLabs\Framework\Security\Model\AuthCodeInterface;

interface AuthCodeManagerInterface
{
    public function generateAuthCode(): AuthCodeInterface;

    public function persistAuthCode(AuthCodeInterface $authCode): void;

    public function revokeAuthCode(AuthCodeInterface $authCode): void;

    public function findAuthCode(string $code): ?AuthCodeInterface;
}
