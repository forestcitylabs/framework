<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterConverter;

use DateTimeInterface;
use ForestCityLabs\Framework\Tests\Fixture\Controller\UserController;
use ForestCityLabs\Framework\Utility\ParameterConverter\DateTimeParameterConverter;
use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConversionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(DateTimeParameterConverter::class)]
class DateTimeParameterConverterTest extends TestCase
{
    #[Test]
    public function convertValidParameters(): void
    {
        $reflection = new ReflectionMethod(UserController::class, 'createdSince');
        $args = [
            'since' => '2018-12-21',
        ];
        $converter = new DateTimeParameterConverter();
        $args = $converter->convertParameters($reflection, $args);
        $this->assertInstanceOf(DateTimeInterface::class, $args['since']);
    }

    #[Test]
    public function convertInvalidParameters(): void
    {
        $this->expectException(ParameterConversionException::class);
        $reflection = new ReflectionMethod(UserController::class, 'createdSince');
        $args = [
            'since' => 'asfdfs',
        ];
        $converter = new DateTimeParameterConverter();
        $args = $converter->convertParameters($reflection, $args);
    }
}
