<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Utility\ParameterConverter;

use ForestCityLabs\Framework\GraphQL\Attribute\InputType;
use ForestCityLabs\Framework\GraphQL\InputResolver;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Tests\Fixture\Controller\AppleController;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use ForestCityLabs\Framework\Tests\Fixture\Miscellaneous\ParameterConverterNegatives;
use ForestCityLabs\Framework\Utility\ParameterConverter\InputTypeConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(InputTypeConverter::class)]
class InputTypeConverterTest extends TestCase
{
    #[Test]
    public function convertValidParameters(): void
    {
        $provider = $this->createMock(MetadataProvider::class);
        $resolver = $this->createMock(InputResolver::class);
        $converter = new InputTypeConverter($provider, $resolver);

        $input = $this->createMock(InputType::class);
        $provider->method('getInputTypeMetadataByClassName')
            ->with(Apple::class)
            ->willReturn($input);
        $apple = $this->createMock(Apple::class);
        $resolver->method('resolve')->with([], $input)->willReturn($apple);

        $reflection = new ReflectionMethod(AppleController::class, 'addApple');
        $args = $converter->convertParameters($reflection, ['apple' => []]);
        $this->assertInstanceOf(Apple::class, $args['apple']);
    }

    #[Test]
    public function cantConvertParameters(): void
    {
        $provider = $this->createMock(MetadataProvider::class);
        $resolver = $this->createMock(InputResolver::class);
        $converter = new InputTypeConverter($provider, $resolver);

        $provider->method('getInputTypeMetadataByClassName')
            ->willReturn(null);

        $reflection = new ReflectionMethod(AppleController::class, 'addApple');
        $args = $converter->convertParameters($reflection, []);
        $this->assertEmpty($args);

        $converter = new InputTypeConverter(
            $this->createMock(MetadataProvider::class),
            $this->createMock(InputResolver::class)
        );
        $reflection = new ReflectionMethod(ParameterConverterNegatives::class, 'cantConvert');
        $args = [];
        $args = $converter->convertParameters($reflection, $args);
        $this->assertEmpty($args);
    }
}
