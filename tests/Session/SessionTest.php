<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Session;

use DateTime;
use ForestCityLabs\Framework\Session\Session;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[CoversClass(Session::class)]
#[Group("session")]
class SessionTest extends TestCase
{
    #[Test]
    public function session()
    {
        $session = (new Session(Uuid::uuid1()))
            ->setExpiry(new DateTime("+1 day"))
            ->setValue('test', 'value');
        $this->assertTrue($session->hasValue('test'));
        $this->assertNotTrue($session->hasValue('other'));
        $this->assertEquals('value', $session->getValue('test'));
        $this->assertEquals(null, $session->getValue('nothing'));
        $this->assertNotTrue($session->isEmpty());
        $session->removeValue('test');
        $this->assertTrue($session->isEmpty());
        $this->assertInstanceOf(UuidInterface::class, $session->getId());
        $this->assertGreaterThan(new DateTime(), $session->getExpiry());
        $data = serialize($session);
        $session = unserialize($data);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('_session')->willReturn($session);
        $session = Session::fromRequest($request);
    }

    #[Test]
    public function invalidSession(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('_session')->willReturn('not_a_session');
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid session');
        Session::fromRequest($request);
    }

    #[Test]
    public function noSession(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())->method('getAttribute')->with('_session')->willReturn(null);
        $this->assertNull(Session::fromRequest($request));
    }
}
