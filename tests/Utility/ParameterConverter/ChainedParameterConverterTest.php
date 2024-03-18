<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterConverter;

use ForestCityLabs\Framework\Tests\Fixture\Miscellaneous\ParameterConverterNegatives;
use ForestCityLabs\Framework\Utility\ParameterConverter\ChainedParameterConverter;
use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConverterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(ChainedParameterConverter::class)]
class ChainedParameterConverterTest extends TestCase
{
    #[Test]
    public function convertParameters(): void
    {
        $converter_one = $this->createMock(ParameterConverterInterface::class);
        $converter_one->expects($this->once())->method('convertParameters');
        $converter_two = $this->createMock(ParameterConverterInterface::class);
        $converter_two->expects($this->once())->method('convertParameters');
        $converter = new ChainedParameterConverter([
            $converter_one,
            $converter_two,
        ]);
        $reflection = new ReflectionMethod(ParameterConverterNegatives::class, 'cantConvert');
        $converter->convertParameters($reflection, []);
    }
}
