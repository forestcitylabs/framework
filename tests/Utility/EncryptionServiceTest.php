<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility;

use ForestCityLabs\Framework\Utility\EncryptionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(EncryptionService::class)]
#[Group('utility')]
class EncryptionServiceTest extends TestCase
{
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        $encryptionKey = base64_encode(random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES));
        $this->encryptionService = new EncryptionService($encryptionKey);
    }

    public function testEncryptDecrypt(): void
    {
        $plaintext = 'Hello, World!';
        $tag = 'optional_tag';

        $encrypted = $this->encryptionService->encrypt($plaintext, $tag);
        $decrypted = $this->encryptionService->decrypt($encrypted, $tag);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testDecryptInvalidData(): void
    {
        $invalidData = 'invalid_base64_string';
        $result = $this->encryptionService->decrypt($invalidData);

        $this->assertNull($result);
    }
}
