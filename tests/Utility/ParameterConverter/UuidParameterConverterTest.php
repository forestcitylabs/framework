<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterConverter;

use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Miscellaneous\ParameterConverterNegatives;
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
        $reflection = new ReflectionMethod(AppleController::class, 'getApple');
        $args = [
            'id' => '07E254B4-E497-4498-9CEF-FEFA51C18EC7',
        ];
        $args = $converter->convertParameters($reflection, $args);
        $this->assertInstanceOf(UuidInterface::class, $args['id']);
    }

    #[Test]
    public function convertInvalidParameters(): void
    {
        $this->expectException(ParameterConversionException::class);
        $converter = new UuidParameterConverter();
        $reflection = new ReflectionMethod(AppleController::class, 'getApple');
        $args = [
            'id' => 'invalid',
        ];
        $args = $converter->convertParameters($reflection, $args);
    }

    #[Test]
    public function cantConvertParameters(): void
    {
        $converter = new UuidParameterConverter();
        $reflection = new ReflectionMethod(ParameterConverterNegatives::class, 'cantConvert');
        $args = [];
        $args = $converter->convertParameters($reflection, $args);
        $this->assertEmpty($args);
    }
}
