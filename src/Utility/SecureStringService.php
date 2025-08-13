<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility;

class SecureStringService
{
    public function generateRandomString(int $length = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }
}
