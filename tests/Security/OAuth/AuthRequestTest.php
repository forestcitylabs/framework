<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\OAuth;

use DateTimeImmutable;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthRequest::class)]
#[Group('security')]
#[Group('oauth')]
class AuthRequestTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $clientId = 'test_client';
        $redirectUri = 'https://example.com/callback';
        $responseType = 'code';
        $expiresAt = new DateTimeImmutable('+5 minutes');

        $authRequest = new AuthRequest($clientId, $redirectUri, $responseType, $expiresAt);

        $this->assertEquals($clientId, $authRequest->getClientId());
        $this->assertEquals($redirectUri, $authRequest->getRedirectUri());
        $this->assertEquals($responseType, $authRequest->getResponseType());
        $this->assertEquals($expiresAt, $authRequest->getExpiresAt());
        $this->assertNull($authRequest->getScope());
        $this->assertNull($authRequest->getState());
        $this->assertNull($authRequest->getCodeChallenge());
        $this->assertNull($authRequest->getCodeChallengeMethod());
        $this->assertNull($authRequest->getNonce());
    }

    public function testConstructorWithAllParameters(): void
    {
        $clientId = 'test_client';
        $redirectUri = 'https://example.com/callback';
        $responseType = 'code';
        $expiresAt = new DateTimeImmutable('+5 minutes');
        $scope = 'read write';
        $state = 'random_state_value';
        $codeChallenge = 'code_challenge_value';
        $codeChallengeMethod = 'S256';
        $nonce = 'random_nonce_value';

        $authRequest = new AuthRequest(
            $clientId,
            $redirectUri,
            $responseType,
            $expiresAt,
            $scope,
            $state,
            $codeChallenge,
            $codeChallengeMethod,
            $nonce
        );

        $this->assertEquals($clientId, $authRequest->getClientId());
        $this->assertEquals($redirectUri, $authRequest->getRedirectUri());
        $this->assertEquals($responseType, $authRequest->getResponseType());
        $this->assertEquals($expiresAt, $authRequest->getExpiresAt());
        $this->assertEquals($scope, $authRequest->getScope());
        $this->assertEquals($state, $authRequest->getState());
        $this->assertEquals($codeChallenge, $authRequest->getCodeChallenge());
        $this->assertEquals($codeChallengeMethod, $authRequest->getCodeChallengeMethod());
        $this->assertEquals($nonce, $authRequest->getNonce());
    }

    public function testClientIdGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $newClientId = 'new_client_id';
        $authRequest->setClientId($newClientId);

        $this->assertEquals($newClientId, $authRequest->getClientId());
    }

    public function testRedirectUriGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $newRedirectUri = 'https://new-example.com/callback';
        $authRequest->setRedirectUri($newRedirectUri);

        $this->assertEquals($newRedirectUri, $authRequest->getRedirectUri());
    }

    public function testResponseTypeGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $newResponseType = 'token';
        $authRequest->setResponseType($newResponseType);

        $this->assertEquals($newResponseType, $authRequest->getResponseType());
    }

    public function testExpiresAtGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $newExpiresAt = new DateTimeImmutable('+10 minutes');
        $authRequest->setExpiresAt($newExpiresAt);

        $this->assertEquals($newExpiresAt, $authRequest->getExpiresAt());
    }

    public function testScopeGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $scope = 'read write admin';
        $authRequest->setScope($scope);

        $this->assertEquals($scope, $authRequest->getScope());

        // Test setting null
        $authRequest->setScope(null);
        $this->assertNull($authRequest->getScope());
    }

    public function testStateGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $state = 'new_random_state';
        $authRequest->setState($state);

        $this->assertEquals($state, $authRequest->getState());

        // Test setting null
        $authRequest->setState(null);
        $this->assertNull($authRequest->getState());
    }

    public function testCodeChallengeGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $codeChallenge = 'new_code_challenge_value';
        $authRequest->setCodeChallenge($codeChallenge);

        $this->assertEquals($codeChallenge, $authRequest->getCodeChallenge());

        // Test setting null
        $authRequest->setCodeChallenge(null);
        $this->assertNull($authRequest->getCodeChallenge());
    }

    public function testCodeChallengeMethodGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $codeChallengeMethod = 'plain';
        $authRequest->setCodeChallengeMethod($codeChallengeMethod);

        $this->assertEquals($codeChallengeMethod, $authRequest->getCodeChallengeMethod());

        // Test setting null
        $authRequest->setCodeChallengeMethod(null);
        $this->assertNull($authRequest->getCodeChallengeMethod());
    }

    public function testNonceGetterAndSetter(): void
    {
        $authRequest = $this->createAuthRequest();

        $nonce = 'new_nonce_value';
        $authRequest->setNonce($nonce);

        $this->assertEquals($nonce, $authRequest->getNonce());

        // Test setting null
        $authRequest->setNonce(null);
        $this->assertNull($authRequest->getNonce());
    }

    public function testSerializeAndUnserialize(): void
    {
        $clientId = 'test_client';
        $redirectUri = 'https://example.com/callback';
        $responseType = 'code';
        $expiresAt = new DateTimeImmutable('2024-12-31 23:59:59');
        $scope = 'read write';
        $state = 'random_state_value';
        $codeChallenge = 'code_challenge_value';
        $codeChallengeMethod = 'S256';
        $nonce = 'random_nonce_value';

        $originalAuthRequest = new AuthRequest(
            $clientId,
            $redirectUri,
            $responseType,
            $expiresAt,
            $scope,
            $state,
            $codeChallenge,
            $codeChallengeMethod,
            $nonce
        );

        // Serialize and unserialize
        $serialized = serialize($originalAuthRequest);
        $unserializedAuthRequest = unserialize($serialized);

        // Verify all properties are preserved
        $this->assertEquals($clientId, $unserializedAuthRequest->getClientId());
        $this->assertEquals($redirectUri, $unserializedAuthRequest->getRedirectUri());
        $this->assertEquals($responseType, $unserializedAuthRequest->getResponseType());
        $this->assertEquals($expiresAt, $unserializedAuthRequest->getExpiresAt());
        $this->assertEquals($scope, $unserializedAuthRequest->getScope());
        $this->assertEquals($state, $unserializedAuthRequest->getState());
        $this->assertEquals($codeChallenge, $unserializedAuthRequest->getCodeChallenge());
        $this->assertEquals($codeChallengeMethod, $unserializedAuthRequest->getCodeChallengeMethod());
        $this->assertEquals($nonce, $unserializedAuthRequest->getNonce());
    }

    public function testSerializeWithMinimalData(): void
    {
        $authRequest = new AuthRequest(
            'minimal_client',
            'https://minimal.com/callback',
            'code',
            new DateTimeImmutable('2024-12-31 12:00:00')
        );

        // Serialize and unserialize
        $serialized = serialize($authRequest);
        $unserializedAuthRequest = unserialize($serialized);

        // Verify required properties are preserved
        $this->assertEquals('minimal_client', $unserializedAuthRequest->getClientId());
        $this->assertEquals('https://minimal.com/callback', $unserializedAuthRequest->getRedirectUri());
        $this->assertEquals('code', $unserializedAuthRequest->getResponseType());
        $this->assertEquals('2024-12-31 12:00:00', $unserializedAuthRequest->getExpiresAt()->format('Y-m-d H:i:s'));

        // Verify optional properties are null
        $this->assertNull($unserializedAuthRequest->getScope());
        $this->assertNull($unserializedAuthRequest->getState());
        $this->assertNull($unserializedAuthRequest->getCodeChallenge());
        $this->assertNull($unserializedAuthRequest->getCodeChallengeMethod());
        $this->assertNull($unserializedAuthRequest->getNonce());
    }

    public function testCustomSerializationMethods(): void
    {
        $authRequest = new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            new DateTimeImmutable('+5 minutes'),
            'read write',
            'state_value',
            'challenge',
            'S256',
            'nonce_value'
        );

        // Test __serialize method
        $serializedData = $authRequest->__serialize();

        $this->assertIsArray($serializedData);
        $this->assertArrayHasKey('client_id', $serializedData);
        $this->assertArrayHasKey('redirect_uri', $serializedData);
        $this->assertArrayHasKey('response_type', $serializedData);
        $this->assertArrayHasKey('expires_at', $serializedData);
        $this->assertArrayHasKey('scope', $serializedData);
        $this->assertArrayHasKey('state', $serializedData);
        $this->assertArrayHasKey('code_challenge', $serializedData);
        $this->assertArrayHasKey('code_challenge_method', $serializedData);
        $this->assertArrayHasKey('nonce', $serializedData);

        $this->assertEquals('test_client', $serializedData['client_id']);
        $this->assertEquals('https://example.com/callback', $serializedData['redirect_uri']);
        $this->assertEquals('code', $serializedData['response_type']);
        $this->assertEquals('read write', $serializedData['scope']);
        $this->assertEquals('state_value', $serializedData['state']);
        $this->assertEquals('challenge', $serializedData['code_challenge']);
        $this->assertEquals('S256', $serializedData['code_challenge_method']);
        $this->assertEquals('nonce_value', $serializedData['nonce']);

        // Test __unserialize method
        $newAuthRequest = new AuthRequest('temp', 'temp', 'temp', new DateTimeImmutable());
        $newAuthRequest->__unserialize($serializedData);

        $this->assertEquals('test_client', $newAuthRequest->getClientId());
        $this->assertEquals('https://example.com/callback', $newAuthRequest->getRedirectUri());
        $this->assertEquals('code', $newAuthRequest->getResponseType());
        $this->assertEquals('read write', $newAuthRequest->getScope());
        $this->assertEquals('state_value', $newAuthRequest->getState());
        $this->assertEquals('challenge', $newAuthRequest->getCodeChallenge());
        $this->assertEquals('S256', $newAuthRequest->getCodeChallengeMethod());
        $this->assertEquals('nonce_value', $newAuthRequest->getNonce());
    }

    public function testImmutabilityOfDateTimeParameter(): void
    {
        $originalDate = new DateTimeImmutable('+5 minutes');
        $authRequest = new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            $originalDate
        );

        // Verify that the stored date is the same instance
        $this->assertSame($originalDate, $authRequest->getExpiresAt());

        // Modify the original date (this should not affect the AuthRequest since DateTimeImmutable is immutable)
        $modifiedDate = $originalDate->add(new \DateInterval('PT10M'));

        // The AuthRequest should still have the original date
        $this->assertEquals($originalDate, $authRequest->getExpiresAt());
        $this->assertNotEquals($modifiedDate, $authRequest->getExpiresAt());
    }

    private function createAuthRequest(): AuthRequest
    {
        return new AuthRequest(
            'test_client',
            'https://example.com/callback',
            'code',
            new DateTimeImmutable('+5 minutes')
        );
    }
}
