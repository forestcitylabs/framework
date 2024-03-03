<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterConverter;

use ForestCityLabs\Framework\Tests\Controller\TestController;
use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConversionException;
use ForestCityLabs\Framework\Utility\ParameterConverter\UuidParameterConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;
use ReflectionMethod;

#[CoversClass(UuidParameterConverter::class)]
class UuidParameterConverterTest extends TestCase
{
    #[Test]
    public function convertValidParameters(): void
    {
        $converter = new UuidParameterConverter();
        $reflection = new ReflectionMethod(TestController::class, 'uuidParameter');
        $args = [
            'uuid' => '07E254B4-E497-4498-9CEF-FEFA51C18EC7',
            'beans' => 'string',
            'train' => 123,
            'check' => true,
        ];
        $args = $converter->convertParameters($reflection, $args);
        $this->assertInstanceOf(UuidInterface::class, $args['uuid']);
    }

    #[Test]
    public function convertInvalidParameters(): void
    {
        $this->expectException(ParameterConversionException::class);
        $converter = new UuidParameterConverter();
        $reflection = new ReflectionMethod(TestController::class, 'uuidParameter');
        $args = [
            'uuid' => 'invalid',
        ];
        $args = $converter->convertParameters($reflection, $args);
    }
}
