<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Oidc;

use ForestCityLabs\Framework\Security\Oidc\OidcClaimRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcClaimRegistry::class)]
#[Group('oidc')]
#[Group('security')]
class OidcClaimRegistryTest extends TestCase
{
    #[Test]
    public function constructorCreatesEmptyRegistry()
    {
        $registry = new OidcClaimRegistry();

        $this->assertEmpty($registry->getClaims());
        $this->assertEmpty($registry->getGroups());
    }

    #[Test]
    public function constructorAcceptsValidClaimsAndGroups()
    {
        $claims = [
            'sub' => false,
            'name' => false,
            'email' => true,
            'admin' => true,
        ];
        
        $groups = [
            'profile' => ['sub', 'name'],
            'email' => ['email'],
            'admin' => ['admin'],
        ];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertEquals(['sub', 'name', 'email', 'admin'], $registry->getClaims());
        $this->assertEquals(['profile', 'email', 'admin'], $registry->getGroups());
    }

    #[Test]
    public function constructorThrowsExceptionForUndefinedClaimInGroup()
    {
        $claims = [
            'sub' => false,
            'name' => false,
        ];
        
        $groups = [
            'profile' => ['sub', 'name', 'email'], // 'email' is not defined in claims
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Claim "email" in group "profile" is not defined.');

        new OidcClaimRegistry($claims, $groups);
    }

    #[Test]
    public function getClaimsReturnsClaimKeys()
    {
        $claims = [
            'sub' => false,
            'name' => false,
            'email' => true,
        ];

        $registry = new OidcClaimRegistry($claims);

        $this->assertEquals(['sub', 'name', 'email'], $registry->getClaims());
    }

    #[Test]
    public function getGroupsReturnsGroupKeys()
    {
        $claims = ['sub' => false, 'name' => false, 'email' => true];
        $groups = [
            'profile' => ['sub', 'name'],
            'email' => ['email'],
        ];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertEquals(['profile', 'email'], $registry->getGroups());
    }

    #[Test]
    public function getGroupReturnsGroupClaims()
    {
        $claims = ['sub' => false, 'name' => false, 'email' => true];
        $groups = [
            'profile' => ['sub', 'name'],
            'email' => ['email'],
        ];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertEquals(['sub', 'name'], $registry->getGroup('profile'));
        $this->assertEquals(['email'], $registry->getGroup('email'));
    }

    #[Test]
    public function getGroupThrowsExceptionForNonExistentGroup()
    {
        $registry = new OidcClaimRegistry();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Group "nonexistent" does not exist.');

        $registry->getGroup('nonexistent');
    }

    #[Test]
    public function filterPrivilegedClaimsRemovesPrivilegedClaims()
    {
        $claims = [
            'sub' => false,        // Not privileged
            'name' => false,       // Not privileged
            'email' => true,       // Privileged
            'admin' => true,       // Privileged
        ];

        $registry = new OidcClaimRegistry($claims);

        $inputClaims = ['sub', 'name', 'email', 'admin'];
        $filtered = $registry->filterPrivilegedClaims($inputClaims);

        $this->assertEquals(['sub', 'name'], $filtered);
    }

    #[Test]
    public function filterPrivilegedClaimsKeepsUndefinedClaims()
    {
        $claims = [
            'sub' => false,
            'email' => true,
        ];

        $registry = new OidcClaimRegistry($claims);

        $inputClaims = ['sub', 'name', 'email', 'custom'];
        $filtered = $registry->filterPrivilegedClaims($inputClaims);

        // 'sub' is not privileged, 'name' and 'custom' are undefined (kept), 'email' is privileged (removed)
        $this->assertEquals(['sub', 'name', 'custom'], $filtered);
    }

    #[Test]
    public function filterPrivilegedClaimsReturnsEmptyArrayWhenAllArePrivileged()
    {
        $claims = [
            'sub' => true,
            'name' => true,
            'email' => true,
        ];

        $registry = new OidcClaimRegistry($claims);

        $inputClaims = ['sub', 'name', 'email'];
        $filtered = $registry->filterPrivilegedClaims($inputClaims);

        $this->assertEmpty($filtered);
    }

    #[Test]
    public function filterPrivilegedClaimsReturnsIndexedArray()
    {
        $claims = [
            'sub' => false,
            'email' => true,
            'name' => false,
        ];

        $registry = new OidcClaimRegistry($claims);

        $inputClaims = ['sub', 'email', 'name'];
        $filtered = $registry->filterPrivilegedClaims($inputClaims);

        // Should return indexed array, not associative
        $this->assertEquals(['sub', 'name'], $filtered);
        $this->assertArrayHasKey(0, $filtered);
        $this->assertArrayHasKey(1, $filtered);
        $this->assertArrayNotHasKey(2, $filtered);
    }

    #[Test]
    public function isValidClaimReturnsTrueForDefinedClaims()
    {
        $claims = [
            'sub' => false,
            'name' => false,
            'email' => true,
        ];

        $registry = new OidcClaimRegistry($claims);

        $this->assertTrue($registry->isValidClaim('sub'));
        $this->assertTrue($registry->isValidClaim('name'));
        $this->assertTrue($registry->isValidClaim('email'));
    }

    #[Test]
    public function isValidClaimReturnsFalseForUndefinedClaims()
    {
        $claims = [
            'sub' => false,
            'name' => false,
        ];

        $registry = new OidcClaimRegistry($claims);

        $this->assertFalse($registry->isValidClaim('email'));
        $this->assertFalse($registry->isValidClaim('admin'));
        $this->assertFalse($registry->isValidClaim('nonexistent'));
    }

    #[Test]
    public function isValidGroupReturnsTrueForDefinedGroups()
    {
        $claims = ['sub' => false, 'name' => false, 'email' => true];
        $groups = [
            'profile' => ['sub', 'name'],
            'email' => ['email'],
        ];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertTrue($registry->isValidGroup('profile'));
        $this->assertTrue($registry->isValidGroup('email'));
    }

    #[Test]
    public function isValidGroupReturnsFalseForUndefinedGroups()
    {
        $claims = ['sub' => false, 'name' => false];
        $groups = ['profile' => ['sub', 'name']];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertFalse($registry->isValidGroup('email'));
        $this->assertFalse($registry->isValidGroup('admin'));
        $this->assertFalse($registry->isValidGroup('nonexistent'));
    }

    #[Test]
    public function constructorValidatesAllGroupClaims()
    {
        $claims = [
            'sub' => false,
            'name' => false,
            'email' => true,
        ];
        
        $groups = [
            'profile' => ['sub', 'name'],
            'contact' => ['email', 'phone'], // 'phone' is not defined
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Claim "phone" in group "contact" is not defined.');

        new OidcClaimRegistry($claims, $groups);
    }

    #[Test]
    public function emptyGroupsAreAllowed()
    {
        $claims = ['sub' => false, 'name' => false];
        $groups = [
            'profile' => ['sub', 'name'],
            'empty' => [],
        ];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertEquals(['profile', 'empty'], $registry->getGroups());
        $this->assertEquals([], $registry->getGroup('empty'));
    }

    #[Test]
    public function duplicateClaimsInGroupsAreAllowed()
    {
        $claims = ['sub' => false, 'name' => false, 'email' => true];
        $groups = [
            'profile' => ['sub', 'name', 'sub'], // Duplicate 'sub'
            'contact' => ['email', 'email'],     // Duplicate 'email'
        ];

        $registry = new OidcClaimRegistry($claims, $groups);

        $this->assertEquals(['sub', 'name', 'sub'], $registry->getGroup('profile'));
        $this->assertEquals(['email', 'email'], $registry->getGroup('contact'));
    }
}