<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterConverter;

use DateTimeInterface;
use ForestCityLabs\Framework\Tests\Controller\TestController;
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
        $reflection = new ReflectionMethod(TestController::class, 'dateTimeParameter');
        $args = [
            'date_time' => '2018-12-21',
            'beans' => 'test',
            'train' => 123,
            'check' => true,
        ];
        $converter = new DateTimeParameterConverter();
        $args = $converter->convertParameters($reflection, $args);
        $this->assertInstanceOf(DateTimeInterface::class, $args['date_time']);
    }

    #[Test]
    public function convertInvalidParameters(): void
    {
        $this->expectException(ParameterConversionException::class);
        $reflection = new ReflectionMethod(TestController::class, 'dateTimeParameter');
        $args = [
            'date_time' => 'asfdfs',
            'beans' => 'test',
            'train' => 123,
            'check' => true,
        ];
        $converter = new DateTimeParameterConverter();
        $args = $converter->convertParameters($reflection, $args);
    }
}
