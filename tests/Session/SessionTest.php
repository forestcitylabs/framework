<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Session;

use ForestCityLabs\Framework\Session\Session;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(Session::class)]
#[Group("session")]
class SessionTest extends TestCase
{
    #[Test]
    public function session()
    {
        $session = new Session(['test' => 'value']);
        $this->assertTrue($session->hasValue('test'));
        $this->assertNotTrue($session->hasValue('other'));
        $this->assertEquals('value', $session->getValue('test'));
        $this->assertEquals(null, $session->getValue('nothing'));
        $this->assertNotTrue($session->isEmpty());
        $session->removeValue('test');
        $this->assertTrue($session->isEmpty());
        $data = serialize($session);
        $session = unserialize($data);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('_session')->willReturn($session);
        $session = Session::fromRequest($request);
    }

    #[Test]
    public function noSession(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('_session')->willReturn(null);
        $this->assertNull(Session::fromRequest($request));
    }
}
