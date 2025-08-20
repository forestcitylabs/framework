<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility;

class EncryptionService
{
    public function __construct(
        private string $encryption_key,
    ) {
    }

    public function decrypt(string $encoded, string $tag = ''): ?string
    {
        $decoded = base64_decode($encoded, true);
        $nonce_length = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (false === $decoded || strlen($decoded) <= $nonce_length) {
            return null;
        }

        $nonce = substr($decoded, 0, $nonce_length);
        $ciphertext = substr($decoded, $nonce_length);

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            $tag,
            $nonce,
            base64_decode($this->encryption_key),
        );

        if (null === $plaintext) {
            return null; // Decryption failed
        }

        return $plaintext;
    }

    public function encrypt(string $plaintext, string $tag = ''): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $tag,
            $nonce,
            base64_decode($this->encryption_key)
        );

        return base64_encode($nonce . $ciphertext);
    }
}
