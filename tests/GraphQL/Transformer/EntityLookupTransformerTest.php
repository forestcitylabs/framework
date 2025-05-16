<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ForestCityLabs\Framework\GraphQL\Transformer\EntityLookupTransformer;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[CoversClass(EntityLookupTransformer::class)]
#[Group('graphql')]
class EntityLookupTransformerTest extends TestCase
{
    #[Test]
    public function transformOutput(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $pa = $this->createMock(PropertyAccessorInterface::class);
        $transformer = new EntityLookupTransformer(Apple::class, $em, $pa);
        $uuid = Uuid::fromString('6aca5a90-3261-11f0-9b76-f2cd93df51d7');
        $apple = new Apple();
        $pa->method('getValue')->with($apple, 'id')->willReturn($uuid);
        $this->assertEquals($transformer->transformOutput($apple), $uuid);
    }

    #[Test]
    public function transformInput(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $pa = $this->createMock(PropertyAccessorInterface::class);
        $transformer = new EntityLookupTransformer(Apple::class, $em, $pa);
        $uuid = Uuid::fromString('6aca5a90-3261-11f0-9b76-f2cd93df51d7');
        $apple = new Apple();
        $em->method('getRepository')->with(Apple::class)->willReturn($repo);
        $repo->method('findOneBy')->willReturn($apple);
        $this->assertEquals($transformer->transformInput($uuid), $apple);
    }

    #[Test]
    public function getTypes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $pa = $this->createMock(PropertyAccessorInterface::class);
        $transformer = new EntityLookupTransformer(Apple::class, $em, $pa);
        $this->assertEquals('ID', $transformer->getGraphQLType());
        $this->assertEquals(Apple::class, $transformer->getNativeType());
    }
}
