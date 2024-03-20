<?php

declare(strict_types=1);

use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConversionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterConversionException::class)]
class ParameterConversionExceptionTest extends TestCase
{
    #[Test]
    public function testException(): void
    {
        $exception = new ParameterConversionException();
        $this->assertFalse($exception->isClientSafe());
        $this->assertSame('internal', $exception->getCategory());
    }
}
