<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc;

use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\Oidc\ClaimResolver;
use ForestCityLabs\Framework\Security\Oidc\OidcClaimRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[CoversClass(ClaimResolver::class)]
#[Group('oidc')]
#[Group('security')]
class ClaimResolverTest extends TestCase
{
    private OidcClaimRegistry $claimRegistry;
    private PropertyAccessor $propertyAccessor;
    private UserInterface $user;
    private ClaimResolver $claimResolver;

    protected function setUp(): void
    {
        $this->claimRegistry = $this->createMock(OidcClaimRegistry::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);
        $this->user = $this->createMock(UserInterface::class);

        $this->claimResolver = new ClaimResolver(
            $this->claimRegistry,
            $this->propertyAccessor
        );
    }

    #[Test]
    public function resolveClaimsWithIndividualClaims()
    {
        $scopes = ['sub', 'name', 'email'];

        // Mock claim registry responses
        $this->claimRegistry->method('isValidClaim')
            ->willReturnMap([
                ['sub', true],
                ['name', true],
                ['email', true],
            ]);

        $this->claimRegistry->method('isValidGroup')
            ->willReturn(false);

        // Mock property accessor responses
        $this->propertyAccessor->method('getValue')
            ->willReturnMap([
                [$this->user, 'sub', '12345'],
                [$this->user, 'name', 'John Doe'],
                [$this->user, 'email', 'john@example.com'],
            ]);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $expected = [
            'sub' => '12345',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function resolveClaimsWithGroups()
    {
        $scopes = ['profile', 'email'];

        // Mock claim registry responses for groups
        $this->claimRegistry->method('isValidClaim')
            ->willReturn(false);

        $this->claimRegistry->method('isValidGroup')
            ->willReturnMap([
                ['profile', true],
                ['email', true],
            ]);

        $this->claimRegistry->method('getGroup')
            ->willReturnMap([
                ['profile', ['sub', 'name', 'given_name', 'family_name']],
                ['email', ['email', 'email_verified']],
            ]);

        // Mock property accessor responses
        $this->propertyAccessor->method('getValue')
            ->willReturnMap([
                [$this->user, 'sub', '12345'],
                [$this->user, 'name', 'John Doe'],
                [$this->user, 'given_name', 'John'],
                [$this->user, 'family_name', 'Doe'],
                [$this->user, 'email', 'john@example.com'],
                [$this->user, 'email_verified', true],
            ]);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $expected = [
            'sub' => '12345',
            'name' => 'John Doe',
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'john@example.com',
            'email_verified' => true,
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function resolveClaimsWithMixedScopesAndClaims()
    {
        $scopes = ['profile', 'custom_claim', 'admin'];

        // Mock claim registry responses
        $this->claimRegistry->method('isValidClaim')
            ->willReturnMap([
                ['profile', false],
                ['custom_claim', true],
                ['admin', false],
            ]);

        $this->claimRegistry->method('isValidGroup')
            ->willReturnMap([
                ['profile', true],
                ['custom_claim', false],
                ['admin', true],
            ]);

        $this->claimRegistry->method('getGroup')
            ->willReturnMap([
                ['profile', ['sub', 'name']],
                ['admin', ['admin_level', 'permissions']],
            ]);

        // Mock property accessor responses
        $this->propertyAccessor->method('getValue')
            ->willReturnMap([
                [$this->user, 'sub', '12345'],
                [$this->user, 'name', 'John Doe'],
                [$this->user, 'custom_claim', 'custom_value'],
                [$this->user, 'admin_level', 5],
                [$this->user, 'permissions', ['read', 'write']],
            ]);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $expected = [
            'sub' => '12345',
            'name' => 'John Doe',
            'custom_claim' => 'custom_value',
            'admin_level' => 5,
            'permissions' => ['read', 'write'],
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function resolveClaimsSkipsInvalidScopes()
    {
        $scopes = ['valid_claim', 'invalid_scope', 'valid_group'];

        // Mock claim registry responses
        $this->claimRegistry->method('isValidClaim')
            ->willReturnMap([
                ['valid_claim', true],
                ['invalid_scope', false],
                ['valid_group', false],
            ]);

        $this->claimRegistry->method('isValidGroup')
            ->willReturnMap([
                ['valid_claim', false],
                ['invalid_scope', false],
                ['valid_group', true],
            ]);

        $this->claimRegistry->method('getGroup')
            ->with('valid_group')
            ->willReturn(['group_claim']);

        // Mock property accessor responses
        $this->propertyAccessor->method('getValue')
            ->willReturnMap([
                [$this->user, 'valid_claim', 'claim_value'],
                [$this->user, 'group_claim', 'group_value'],
            ]);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $expected = [
            'valid_claim' => 'claim_value',
            'group_claim' => 'group_value',
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function resolveClaimsReturnsEmptyForAllInvalidScopes()
    {
        $scopes = ['invalid1', 'invalid2', 'invalid3'];

        // Mock claim registry responses
        $this->claimRegistry->method('isValidClaim')
            ->willReturn(false);

        $this->claimRegistry->method('isValidGroup')
            ->willReturn(false);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $this->assertEmpty($result);
    }

    #[Test]
    public function resolveClaimsHandlesEmptyScopes()
    {
        $scopes = [];

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $this->assertEmpty($result);
    }

    #[Test]
    public function resolveClaimsUsesPropertyAccessorCorrectly()
    {
        $scopes = ['nested.property'];

        $this->claimRegistry->method('isValidClaim')
            ->with('nested.property')
            ->willReturn(true);

        $this->claimRegistry->method('isValidGroup')
            ->with('nested.property')
            ->willReturn(false);

        $this->propertyAccessor
            ->expects($this->once())
            ->method('getValue')
            ->with($this->user, 'nested.property')
            ->willReturn('nested_value');

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $expected = ['nested.property' => 'nested_value'];
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function resolveClaimsHandlesNullValues()
    {
        $scopes = ['nullable_claim'];

        $this->claimRegistry->method('isValidClaim')
            ->with('nullable_claim')
            ->willReturn(true);

        $this->claimRegistry->method('isValidGroup')
            ->willReturn(false);

        $this->propertyAccessor->method('getValue')
            ->with($this->user, 'nullable_claim')
            ->willReturn(null);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $expected = ['nullable_claim' => null];
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function resolveClaimsHandlesDuplicateClaimsFromDifferentGroups()
    {
        $scopes = ['profile', 'identity'];

        $this->claimRegistry->method('isValidClaim')
            ->willReturn(false);

        $this->claimRegistry->method('isValidGroup')
            ->willReturnMap([
                ['profile', true],
                ['identity', true],
            ]);

        $this->claimRegistry->method('getGroup')
            ->willReturnMap([
                ['profile', ['sub', 'name']],
                ['identity', ['sub', 'email']], // 'sub' appears in both groups
            ]);

        $this->propertyAccessor->method('getValue')
            ->willReturnMap([
                [$this->user, 'sub', '12345'],
                [$this->user, 'name', 'John Doe'],
                [$this->user, 'email', 'john@example.com'],
            ]);

        $result = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        // Should contain duplicate 'sub' entries
        $expected = [
            'sub' => '12345',
            'name' => 'John Doe',
            'sub' => '12345', // This will overwrite the first 'sub' in the final array
            'email' => 'john@example.com',
        ];

        // In reality, the second 'sub' will overwrite the first due to array key behavior
        $this->assertEquals([
            'sub' => '12345',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], $result);
    }

    #[Test]
    public function resolveClaimsReturnsIterable()
    {
        $scopes = ['sub'];

        $this->claimRegistry->method('isValidClaim')
            ->with('sub')
            ->willReturn(true);

        $this->claimRegistry->method('isValidGroup')
            ->willReturn(false);

        $this->propertyAccessor->method('getValue')
            ->with($this->user, 'sub')
            ->willReturn('12345');

        $result = $this->claimResolver->resolveClaims($scopes, $this->user);

        $this->assertIsIterable($result);

        // Verify we can iterate multiple times
        $array1 = iterator_to_array($result);
        $array2 = iterator_to_array($this->claimResolver->resolveClaims($scopes, $this->user));

        $this->assertEquals($array1, $array2);
    }
}
