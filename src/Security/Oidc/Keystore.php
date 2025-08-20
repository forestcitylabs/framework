<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc;

final class Keystore
{
    public function __construct(
        private array $keys = [],
    ) {
        // Iterate over keys generating key objects.
        foreach ($keys as $key => $file) {
            $this->keys[$key] = [
                'private' => @file_get_contents($file),
                'public' => openssl_pkey_get_details(openssl_pkey_get_private(@file_get_contents($file)))['key'],
            ];
        }
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getKey(string $key): array
    {
        return $this->keys[$key] ?? throw new \InvalidArgumentException("Key '$key' not found in keystore.");
    }
}
