<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security;

use ForestCityLabs\Framework\Security\RoleRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoleRegistry::class)]
class RoleRegistryTest extends TestCase
{
    private array $roles = [
        'admin' => true,
        'write' => true,
        'read' => false,
    ];

    #[Test]
    public function getAllRoles(): void
    {
        $registry = new RoleRegistry($this->roles);
        $this->assertEquals(array_keys($this->roles), $registry->getAllRoles());
    }

    #[Test]
    public function roleExists(): void
    {
        $registry = new RoleRegistry($this->roles);
        $this->assertEquals(true, $registry->roleExists('admin'));
        $this->assertEquals(false, $registry->roleExists('nope'));
    }

    #[Test]
    public function filterPrivilegedRoles(): void
    {
        $registry = new RoleRegistry($this->roles);
        $roles = $registry->filterPrivilegedRoles(['read', 'write']);
        $this->assertEquals(['read'], $roles);
    }
}
