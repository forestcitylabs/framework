<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Security\Attribute;

use ForestCityLabs\Framework\Security\Attribute\RequiresScope;
use ForestCityLabs\Framework\Security\Exception\InsufficientScopeException;
use ForestCityLabs\Framework\Security\Exception\UnauthorizedException;
use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

#[CoversClass(RequiresScope::class)]
#[UsesClass(UnauthorizedException::class)]
class RequiresScopeTest extends TestCase
{
    #[Test]
    public function noAccessToken(): void
    {
        $attribute = new RequiresScope('admin');
        $this->expectException(UnauthorizedException::class);
        $attribute->checkRequirement($this->createMock(ServerRequestInterface::class));
    }

    #[Test]
    public function invalidToken(): void
    {
        $attribute = new RequiresScope('admin');
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('_access_token')->willReturn(new stdClass());
        $this->expectException(UnauthorizedException::class);
        $attribute->checkRequirement($request);
    }

    #[Test]
    public function tokenDoesNotHaveScope(): void
    {
        $attribute = new RequiresScope('admin');
        $request = $this->createMock(ServerRequestInterface::class);
        $token = $this->createMock(AccessTokenInterface::class);
        $request->method('getAttribute')->with('_access_token')->willReturn($token);
        $token->method('hasScope')->willReturn(false);
        $this->expectException(InsufficientScopeException::class);
        $attribute->checkRequirement($request);
    }
}
