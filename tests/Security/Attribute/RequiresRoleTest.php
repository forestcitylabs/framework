<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Attribute;

use ForestCityLabs\Framework\Security\Attribute\RequiresRole;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

#[CoversClass(RequiresRole::class)]
#[UsesClass(UnauthorizedException::class)]
#[Group("security")]
class RequiresRoleTest extends TestCase
{
    #[Test]
    public function noAccessToken(): void
    {
        $attribute = new RequiresRole('admin');
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->with('_access_token')
            ->willReturn(null);
        $this->expectException(UnauthorizedException::class);
        $attribute->checkRequirement($request);
    }

    #[Test]
    public function invalidToken(): void
    {
        $attribute = new RequiresRole('admin');
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')
            ->with('_access_token')
            ->willReturn(new stdClass());
        $this->expectException(UnauthorizedException::class);
        $attribute->checkRequirement($request);
    }
}
