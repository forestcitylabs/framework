<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security;

use ForestCityLabs\Framework\Security\ScopeRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopeRegistry::class)]
class ScopeRegistryTest extends TestCase
{
    private array $scopes = [
        'admin' => true,
        'write' => true,
        'read' => false,
    ];

    #[Test]
    public function getAllScopes(): void
    {
        $registry = new ScopeRegistry($this->scopes);
        $this->assertEquals(array_keys($this->scopes), $registry->getAllScopes());
    }

    #[Test]
    public function scopeExists(): void
    {
        $registry = new ScopeRegistry($this->scopes);
        $this->assertEquals(true, $registry->scopeExists(ScopeRegistry::ALL));
        $this->assertEquals(true, $registry->scopeExists('admin'));
        $this->assertEquals(false, $registry->scopeExists('nope'));
    }

    #[Test]
    public function filterPrivilegedScopes(): void
    {
        $registry = new ScopeRegistry($this->scopes);
        $scopes = $registry->filterPrivilegedScopes($this->scopes);
        $this->assertEquals(['read'], $scopes);
    }
}
