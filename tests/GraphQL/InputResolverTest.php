<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ForestCityLabs\Framework\GraphQL\Attribute\AbstractType;
use ForestCityLabs\Framework\GraphQL\Attribute\Argument;
use ForestCityLabs\Framework\GraphQL\Attribute\InputType;
use ForestCityLabs\Framework\GraphQL\InputResolver;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Tests\Entity\AnotherTestEntity;
use ForestCityLabs\Framework\Tests\Entity\TestEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Predis\Command\Argument\Search\SchemaFields\AbstractField;
use Ramsey\Uuid\Uuid;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[CoversClass(InputResolver::class)]
#[UsesClass(AbstractField::class)]
#[UsesClass(Argument::class)]
#[UsesClass(AbstractType::class)]
#[UsesClass(InputType::class)]
#[Group("graphql")]
class InputResolverTest extends TestCase
{
    #[Test]
    public function resolveValid(): void
    {
        // Create a new uuid and entity.
        $uuid = Uuid::uuid4();
        $another_entity = new AnotherTestEntity();

        // Mock the services.
        $accessor = new PropertyAccessor();
        $provider = $this->createStub(MetadataProvider::class);
        $em = $this->createStub(EntityManagerInterface::class);
        $repo = $this->createStub(EntityRepository::class);

        // The entity manager will be used to resolve the entity reference.
        $em->method('getRepository')
            ->with(AnotherTestEntity::class)
            ->willReturn($repo);
        $repo->method('findOneBy')
            ->with(['id' => $uuid])
            ->willReturn($another_entity);

        // Create the input resolver.
        $resolver = new InputResolver($accessor, $provider, $em);

        // Mock the values to resolve.
        $created = new DateTimeImmutable();
        $values = [
            'ref' => $uuid,
            'created' => $created,
        ];

        // Create an input type that is mapped to the entity correctly.
        $input = (new InputType('TestEntityInput'))
            ->setClassName(TestEntity::class)
            ->addArgument((new Argument('ref', type: 'ID'))->setAttributeType('property')->setAttributeName('ref'))
            ->addArgument((new Argument('created', type: 'String'))->setAttributeType('property')->setAttributeName('created'));

        // Activate the resolver.
        $object = $resolver->resolve($values, $input);

        // Make assertions.
        $this->assertEquals($created, $object->getCreated());
        $this->assertEquals($another_entity, $object->getRef());
    }
}
