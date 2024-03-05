<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use ForestCityLabs\Framework\GraphQL\Attribute\Field;
use ForestCityLabs\Framework\GraphQL\PropertyFieldResolver;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\ValueTransformerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[CoversClass(PropertyFieldResolver::class)]
#[UsesClass(Field::class)]
#[Group('graphql')]
class PropertyFieldResolverTest extends TestCase
{
    #[Test]
    public function resolve(): void
    {
        $accessor = new PropertyAccessor();
        $transformer = $this->createMock(ValueTransformerInterface::class);
        $transformer->method('transformOutput')->willReturnArgument(0);

        $resolver = new PropertyFieldResolver($accessor, $transformer);

        $object = new \stdClass();
        $object->test = "test";
        $field = new Field('test', type: 'String');
        $field->setAttributeName('test');
        $output = $resolver->resolveField($field, $object);
        $this->assertEquals('test', $output);
    }
}
