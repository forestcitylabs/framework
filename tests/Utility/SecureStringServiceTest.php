<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility;

use ForestCityLabs\Framework\Utility\SecureStringService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(SecureStringService::class)]
#[Group('utility')]
class SecureStringServiceTest extends TestCase
{
    private SecureStringService $secureStringService;

    protected function setUp(): void
    {
        $this->secureStringService = new SecureStringService();
    }

    public function testGenerateRandomString(): void
    {
        $length = 32;
        $randomString = $this->secureStringService->generateRandomString($length);

        $this->assertIsString($randomString);
    }
}
