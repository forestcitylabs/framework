<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Session;

use ForestCityLabs\Framework\Session\Flash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Flash::class)]
class FlashTest extends TestCase
{
    #[Test]
    public function addFlash(): void
    {
        $flash = new Flash();
        $flash->addFlash('success', 'test');
        $flash->addFlash('success', 'more_test');
        $this->assertFalse($flash->isEmpty());
        $this->assertEquals(['test', 'more_test'], $flash->getFlash('success'));
        $this->assertEmpty($flash->getFlash('success'));
        $this->assertTrue($flash->isEmpty());
    }

    #[Test]
    public function getAllFlashes(): void
    {
        $flash = new Flash();
        $flash->addFlash('success', 'test');
        $flash->addFlash('success', 'more_test');
        $this->assertFalse($flash->isEmpty());
        $this->assertEquals(['success' => ['test', 'more_test']], $flash->getAll());
        $this->assertEmpty($flash->getFlash('success'));
        $this->assertTrue($flash->isEmpty());
    }

    #[Test]
    public function serialize(): void
    {
        $flash = new Flash();
        $flash->addFlash('success', 'test');
        $flash->addFlash('success', 'more_test');
        $data = serialize($flash);
        $flash = unserialize($data);
        $this->assertFalse($flash->isEmpty());
        $this->assertEquals(['success' => ['test', 'more_test']], $flash->getAll());
        $this->assertEmpty($flash->getFlash('success'));
        $this->assertTrue($flash->isEmpty());
    }
}
