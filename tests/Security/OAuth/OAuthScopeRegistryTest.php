<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\OAuth;

use ForestCityLabs\Framework\Security\OAuth\OAuthScopeRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OAuthScopeRegistry::class)]
#[Group('oauth')]
#[Group('security')]
class OAuthScopeRegistryTest extends TestCase
{
    #[Test]
    public function constructorCreatesEmptyRegistry()
    {
        $registry = new OAuthScopeRegistry([]);

        $this->assertEmpty($registry->getAllScopes());
    }

    #[Test]
    public function constructorAcceptsValidScopes()
    {
        $scopes = [
            'read' => false,
            'write' => false,
            'admin' => true,
            'openid' => false,
            'profile' => false,
            'email' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $this->assertEquals(['read', 'write', 'admin', 'openid', 'profile', 'email'], $registry->getAllScopes());
    }

    #[Test]
    public function getAllScopesReturnsOnlyScopeKeys()
    {
        $scopes = [
            'read' => false,
            'write' => true,
            'admin' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $result = $registry->getAllScopes();

        $this->assertEquals(['read', 'write', 'admin'], $result);
        $this->assertContainsOnly('string', $result);
    }

    #[Test]
    public function getAllScopesReturnsEmptyArrayForEmptyRegistry()
    {
        $registry = new OAuthScopeRegistry([]);

        $this->assertEmpty($registry->getAllScopes());
        $this->assertIsArray($registry->getAllScopes());
    }

    #[Test]
    public function filterPrivilegedScopesRemovesPrivilegedScopes()
    {
        $scopes = [
            'read' => false,        // Not privileged
            'write' => false,       // Not privileged
            'admin' => true,        // Privileged
            'delete' => true,       // Privileged
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['read', 'write', 'admin', 'delete'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        $this->assertEquals(['read', 'write'], $filtered);
    }

    #[Test]
    public function filterPrivilegedScopesKeepsUndefinedScopes()
    {
        $scopes = [
            'read' => false,
            'admin' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['read', 'undefined', 'admin', 'custom'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        // 'read' is not privileged (kept), 'undefined' and 'custom' are not defined (kept), 'admin' is privileged (removed)
        $this->assertEquals(['read', 'undefined', 'custom'], $filtered);
    }

    #[Test]
    public function filterPrivilegedScopesReturnsEmptyArrayWhenAllArePrivileged()
    {
        $scopes = [
            'read' => true,
            'write' => true,
            'admin' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['read', 'write', 'admin'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        $this->assertEmpty($filtered);
    }

    #[Test]
    public function filterPrivilegedScopesReturnsAllWhenNoneArePrivileged()
    {
        $scopes = [
            'read' => false,
            'write' => false,
            'profile' => false,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['read', 'write', 'profile'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        $this->assertEquals(['read', 'write', 'profile'], $filtered);
    }

    #[Test]
    public function filterPrivilegedScopesReturnsIndexedArray()
    {
        $scopes = [
            'read' => false,
            'admin' => true,
            'write' => false,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['read', 'admin', 'write'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        // Should return indexed array, not associative
        $this->assertEquals(['read', 'write'], $filtered);
        $this->assertArrayHasKey(0, $filtered);
        $this->assertArrayHasKey(1, $filtered);
        $this->assertArrayNotHasKey(2, $filtered);
    }

    #[Test]
    public function filterPrivilegedScopesHandlesEmptyInput()
    {
        $scopes = [
            'read' => false,
            'admin' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $filtered = $registry->filterPrivilegedScopes([]);

        $this->assertEmpty($filtered);
    }

    #[Test]
    public function isValidScopeReturnsTrueForDefinedScopes()
    {
        $scopes = [
            'read' => false,
            'write' => false,
            'admin' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $this->assertTrue($registry->isValidScope('read'));
        $this->assertTrue($registry->isValidScope('write'));
        $this->assertTrue($registry->isValidScope('admin'));
    }

    #[Test]
    public function isValidScopeReturnsFalseForUndefinedScopes()
    {
        $scopes = [
            'read' => false,
            'write' => false,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $this->assertFalse($registry->isValidScope('admin'));
        $this->assertFalse($registry->isValidScope('delete'));
        $this->assertFalse($registry->isValidScope('nonexistent'));
    }

    #[Test]
    public function isValidScopeReturnsFalseForEmptyRegistry()
    {
        $registry = new OAuthScopeRegistry([]);

        $this->assertFalse($registry->isValidScope('any'));
        $this->assertFalse($registry->isValidScope('scope'));
    }

    #[Test]
    public function privilegedScopeLogicWorksCorrectly()
    {
        $scopes = [
            'public_read' => false,     // Not privileged
            'public_write' => false,    // Not privileged
            'admin_read' => true,       // Privileged
            'admin_write' => true,      // Privileged
            'admin_delete' => true,     // Privileged
        ];

        $registry = new OAuthScopeRegistry($scopes);

        // Test that privileged scopes are identified correctly
        $this->assertTrue($registry->isValidScope('admin_read'));
        $this->assertTrue($registry->isValidScope('admin_write'));
        $this->assertTrue($registry->isValidScope('admin_delete'));
        $this->assertTrue($registry->isValidScope('public_read'));
        $this->assertTrue($registry->isValidScope('public_write'));

        // Test filtering
        $allScopes = ['public_read', 'public_write', 'admin_read', 'admin_write', 'admin_delete'];
        $filtered = $registry->filterPrivilegedScopes($allScopes);
        
        $this->assertEquals(['public_read', 'public_write'], $filtered);
    }

    #[Test]
    public function scopeNamesCanHaveSpecialCharacters()
    {
        $scopes = [
            'user:read' => false,
            'user:write' => true,
            'repo:admin' => true,
            'openid' => false,
            'profile.email' => false,
            'scope-with-dashes' => false,
            'scope_with_underscores' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $expectedScopes = [
            'user:read',
            'user:write', 
            'repo:admin',
            'openid',
            'profile.email',
            'scope-with-dashes',
            'scope_with_underscores'
        ];

        $this->assertEquals($expectedScopes, $registry->getAllScopes());

        foreach ($expectedScopes as $scope) {
            $this->assertTrue($registry->isValidScope($scope));
        }
    }

    #[Test]
    public function filterPrivilegedScopesPreservesOrder()
    {
        $scopes = [
            'first' => false,
            'second' => true,
            'third' => false,
            'fourth' => true,
            'fifth' => false,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['first', 'second', 'third', 'fourth', 'fifth'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        $this->assertEquals(['first', 'third', 'fifth'], $filtered);
    }

    #[Test]
    public function handlesMixedPrivilegedAndUndefinedScopes()
    {
        $scopes = [
            'defined_public' => false,
            'defined_private' => true,
        ];

        $registry = new OAuthScopeRegistry($scopes);

        $inputScopes = ['defined_public', 'undefined_scope', 'defined_private', 'another_undefined'];
        $filtered = $registry->filterPrivilegedScopes($inputScopes);

        // Should keep: defined_public (not privileged), undefined_scope (undefined), another_undefined (undefined)
        // Should remove: defined_private (privileged)
        $this->assertEquals(['defined_public', 'undefined_scope', 'another_undefined'], $filtered);
    }
}