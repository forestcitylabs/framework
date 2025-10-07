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
        'admin',
        'write',
        'read',
    ];

    #[Test]
    public function getAllRoles(): void
    {
        $registry = new RoleRegistry($this->roles);
        $this->assertEquals($this->roles, $registry->getAllRoles());
    }

    #[Test]
    public function roleExists(): void
    {
        $registry = new RoleRegistry($this->roles);
        $this->assertTrue($registry->roleExists('admin'));
        $this->assertFalse($registry->roleExists('nope'));
    }

    #[Test]
    public function getRolesUnder(): void
    {
        $hierarchy = [
            'admin' => ['write', 'read'],
            'write' => ['read'],
        ];
        $registry = new RoleRegistry($this->roles, $hierarchy);
        $roles = $registry->getRolesUnder('admin');
        $this->assertContains('write', $roles);
        $this->assertContains('read', $roles);
    }
}
