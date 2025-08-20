<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc;

use ForestCityLabs\Framework\Security\Oidc\Keystore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(Keystore::class)]
#[Group('security')]
#[Group('oidc')]
class KeystoreTest extends TestCase
{
    private array $tempKeyFiles = [];

    protected function tearDown(): void
    {
        // Clean up temporary key files
        foreach ($this->tempKeyFiles as $keyFile) {
            if (file_exists($keyFile)) {
                unlink($keyFile);
            }
        }
        $this->tempKeyFiles = [];
    }

    private function createTempKeyFile(string $keyId = 'test_key'): string
    {
        $tempDir = sys_get_temp_dir();
        $keyFile = $tempDir . "/{$keyId}.pem";

        $privateKey = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($privateKey, $privateKeyPem);
        file_put_contents($keyFile, $privateKeyPem);

        $this->tempKeyFiles[] = $keyFile;
        return $keyFile;
    }

    public function testConstructorWithSingleKeyFile(): void
    {
        $keyFile = $this->createTempKeyFile('single_key');
        $keystore = new Keystore(['test_key' => $keyFile]);

        $this->assertInstanceOf(Keystore::class, $keystore);
    }

    public function testConstructorWithMultipleKeyFiles(): void
    {
        $keyFiles = [
            'key1' => $this->createTempKeyFile('key1'),
            'key2' => $this->createTempKeyFile('key2'),
            'key3' => $this->createTempKeyFile('key3'),
        ];

        $keystore = new Keystore($keyFiles);

        $this->assertInstanceOf(Keystore::class, $keystore);
    }

    public function testConstructorWithEmptyArray(): void
    {
        $keystore = new Keystore([]);

        $this->assertInstanceOf(Keystore::class, $keystore);
    }

    public function testGetKeysReturnsAllKeys(): void
    {
        $keyFiles = [
            'key1' => $this->createTempKeyFile('key1'),
            'key2' => $this->createTempKeyFile('key2'),
        ];

        $keystore = new Keystore($keyFiles);
        $keys = $keystore->getKeys();

        $this->assertIsArray($keys);
        $this->assertCount(2, $keys);
        $this->assertArrayHasKey('key1', $keys);
        $this->assertArrayHasKey('key2', $keys);

        // Verify each key has private and public components
        foreach ($keys as $keyId => $keyData) {
            $this->assertArrayHasKey('private', $keyData);
            $this->assertArrayHasKey('public', $keyData);
            $this->assertIsString($keyData['private']);
            $this->assertIsString($keyData['public']);
            
            // Verify the private key is valid
            $privateKey = openssl_pkey_get_private($keyData['private']);
            $this->assertTrue(is_resource($privateKey) || $privateKey instanceof \OpenSSLAsymmetricKey);
        }
    }

    public function testGetKeysWithEmptyKeystore(): void
    {
        $keystore = new Keystore([]);
        $keys = $keystore->getKeys();

        $this->assertIsArray($keys);
        $this->assertEmpty($keys);
    }

    public function testGetKeyReturnsSpecificKey(): void
    {
        $keyFile = $this->createTempKeyFile('specific_key');
        $keystore = new Keystore(['test_key' => $keyFile]);

        $key = $keystore->getKey('test_key');

        $this->assertIsArray($key);
        $this->assertArrayHasKey('private', $key);
        $this->assertArrayHasKey('public', $key);
        $this->assertIsString($key['private']);
        $this->assertIsString($key['public']);

        // Verify the private key is valid
        $privateKey = openssl_pkey_get_private($key['private']);
        $this->assertTrue(is_resource($privateKey) || $privateKey instanceof \OpenSSLAsymmetricKey);
    }

    public function testGetKeyThrowsExceptionForNonExistentKey(): void
    {
        $keyFile = $this->createTempKeyFile('existing_key');
        $keystore = new Keystore(['existing_key' => $keyFile]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Key 'non_existent_key' not found in keystore.");

        $keystore->getKey('non_existent_key');
    }

    public function testPublicKeyIsExtractedFromPrivateKey(): void
    {
        $keyFile = $this->createTempKeyFile('test_public');
        $keystore = new Keystore(['test_key' => $keyFile]);

        $key = $keystore->getKey('test_key');

        // Verify that the public key is extracted from the private key
        $privateKeyContents = file_get_contents($keyFile);
        $privateKey = openssl_pkey_get_private($privateKeyContents);
        $keyDetails = openssl_pkey_get_details($privateKey);
        $expectedPublicKey = $keyDetails['key'];

        $this->assertEquals($expectedPublicKey, $key['public']);
    }

    public function testPrivateKeyIsStoredDirectly(): void
    {
        $keyFile = $this->createTempKeyFile('test_private');
        $keystore = new Keystore(['test_key' => $keyFile]);

        $key = $keystore->getKey('test_key');

        // Verify that the private key is stored as-is from the file
        $expectedPrivateKey = file_get_contents($keyFile);

        $this->assertEquals($expectedPrivateKey, $key['private']);
    }

    public function testConstructorThrowsExceptionForNonExistentFile(): void
    {
        $nonExistentFile = '/path/that/does/not/exist.pem';

        $this->expectException(\Error::class);

        new Keystore(['test_key' => $nonExistentFile]);
    }

    public function testConstructorThrowsExceptionForInvalidKeyFile(): void
    {
        $tempDir = sys_get_temp_dir();
        $invalidKeyFile = $tempDir . '/invalid_key.pem';
        file_put_contents($invalidKeyFile, 'This is not a valid private key');
        $this->tempKeyFiles[] = $invalidKeyFile;

        $this->expectException(\Error::class);

        new Keystore(['invalid_key' => $invalidKeyFile]);
    }

    public function testKeyIdsArePreservedFromConstructor(): void
    {
        $keyFiles = [
            'primary_key' => $this->createTempKeyFile('primary'),
            'secondary_key' => $this->createTempKeyFile('secondary'),
            'backup_key' => $this->createTempKeyFile('backup'),
        ];

        $keystore = new Keystore($keyFiles);
        $keys = $keystore->getKeys();

        $this->assertArrayHasKey('primary_key', $keys);
        $this->assertArrayHasKey('secondary_key', $keys);
        $this->assertArrayHasKey('backup_key', $keys);

        // Verify we can retrieve keys by their IDs
        $this->assertIsArray($keystore->getKey('primary_key'));
        $this->assertIsArray($keystore->getKey('secondary_key'));
        $this->assertIsArray($keystore->getKey('backup_key'));
    }

    public function testRsaKeyTypesAreSupported(): void
    {
        // Create RSA key with different bit sizes
        $keyConfigs = [
            ['private_key_bits' => 1024],
            ['private_key_bits' => 2048],
            ['private_key_bits' => 4096],
        ];

        foreach ($keyConfigs as $index => $config) {
            $tempDir = sys_get_temp_dir();
            $keyFile = $tempDir . "/rsa_key_{$config['private_key_bits']}.pem";

            $privateKey = openssl_pkey_new(array_merge([
                'digest_alg' => 'sha256',
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ], $config));

            openssl_pkey_export($privateKey, $privateKeyPem);
            file_put_contents($keyFile, $privateKeyPem);
            $this->tempKeyFiles[] = $keyFile;

            $keystore = new Keystore(["rsa_{$config['private_key_bits']}" => $keyFile]);
            $key = $keystore->getKey("rsa_{$config['private_key_bits']}");

            $this->assertArrayHasKey('private', $key);
            $this->assertArrayHasKey('public', $key);

            // Verify key details
            $privateKeyResource = openssl_pkey_get_private($key['private']);
            $keyDetails = openssl_pkey_get_details($privateKeyResource);
            
            $this->assertEquals(OPENSSL_KEYTYPE_RSA, $keyDetails['type']);
            $this->assertEquals($config['private_key_bits'], $keyDetails['bits']);
        }
    }
}