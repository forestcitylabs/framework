<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Session\Driver;

use DateTime;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class AbstractSessionDriverTestCase extends TestCase
{
    protected SessionDriverInterface $driver;

    #[Test]
    #[DoesNotPerformAssertions]
    public function saveSession(): void
    {
        $session = new Session(Uuid::uuid4());
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test_array', ['data']);
        $this->driver->save($session);
        $this->driver->save($session);
    }

    #[Test]
    public function loadSession(): void
    {
        $uuid = Uuid::uuid4();
        $session = new Session($uuid);
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test', ['data']);
        $this->driver->save($session);

        $session = $this->driver->load($uuid);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertEquals(['data'], $session->getValue('test'));
        $this->assertNull($this->driver->load(Uuid::uuid4()));
    }

    #[Test]
    public function deleteSession(): void
    {
        $uuid = Uuid::uuid4();
        $session = new Session($uuid);
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test', ['data']);
        $this->driver->save($session);
        $this->driver->delete($session);
        $this->assertNull($this->driver->load($uuid));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function deleteAllSessions(): void
    {
        $session = new Session(Uuid::uuid4());
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test', ['data']);
        $this->driver->save($session);
        $session = new Session(Uuid::uuid4());
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test', ['data']);
        $this->driver->save($session);
        $this->driver->deleteAll();
    }

    #[Test]
    public function loadAllSessions(): void
    {
        $session = new Session(Uuid::uuid4());
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test', ['data']);
        $this->driver->save($session);
        $session = new Session(Uuid::uuid4());
        $session->setExpiry(new DateTime('+1 day'));
        $session->setValue('test', ['data']);
        $this->driver->save($session);
        foreach ($this->driver->loadAll() as $session) {
            $this->assertInstanceOf(Session::class, $session);
        }
    }
}
