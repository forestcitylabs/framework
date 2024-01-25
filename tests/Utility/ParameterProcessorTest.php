<?php

namespace ForestCityLabs\Framework\Tests\Utility;

use ForestCityLabs\Framework\Utility\FunctionReflectionFactory;
use ForestCityLabs\Framework\Utility\ParameterConverter\ParameterConverterInterface;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use ForestCityLabs\Framework\Utility\ParameterResolver\ParameterResolverInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterProcessor::class)]
#[UsesClass(FunctionReflectionFactory::class)]
class ParameterProcessorTest extends TestCase
{
    #[Test]
    public function processParameters()
    {
        $resolver = $this->createMock(ParameterResolverInterface::class);
        $converter = $this->createMock(ParameterConverterInterface::class);
        $processor = new ParameterProcessor($resolver, $converter);
        $params = [
            1,
            'test',
            new \stdClass(),
        ];
        $resolver->expects($this->once())
            ->method('resolveParameters')
            ->willReturn($params);

        $converter->expects($this->once())
            ->method('convertParameters')
            ->willReturn($params);
        $return = $processor->processParameters(function (int $num, string $test, \stdClass $obj) {
        }, $params);
        $this->assertEquals($params, $return);
    }
}
