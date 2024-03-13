<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Attribute;

use ForestCityLabs\Framework\Security\Attribute\RequiresRole;
use ForestCityLabs\Framework\Security\Exception\ForbiddenException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
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

    #[Test]
    public function userDoesNotHaveRole(): void
    {
        // Create the attribute.
        $attribute = new RequiresRole('admin');

        // Mock the values.
        $request = $this->createMock(ServerRequestInterface::class);
        $token = $this->createMock(AccessTokenInterface::class);
        $user = $this->createMock(UserInterface::class);

        // Configure the values.
        $request->method('getAttribute')->with('_access_token')->willReturn($token);
        $token->method('getUser')->willReturn($user);
        $user->method('hasRole')->with('admin')->willReturn(false);

        // Expect the forbidden exception.
        $this->expectException(ForbiddenException::class);
        $attribute->checkRequirement($request);
    }
}
