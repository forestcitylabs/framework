<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\GraphQL\Diff;

use ForestCityLabs\Framework\GraphQL\Diff\ArgumentDiff;
use ForestCityLabs\Framework\GraphQL\Diff\EnumTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\FieldDiff;
use ForestCityLabs\Framework\GraphQL\Diff\InputFieldDiff;
use ForestCityLabs\Framework\GraphQL\Diff\InputObjectTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\InterfaceTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\ObjectTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaComparator;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaDiff;
use ForestCityLabs\Framework\GraphQL\Diff\ValueDiff;
use GraphQL\Utils\BuildSchema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SchemaComparator::class)]
#[UsesClass(EnumTypeDiff::class)]
#[UsesClass(ObjectTypeDiff::class)]
#[UsesClass(InputObjectTypeDiff::class)]
#[UsesClass(InputFieldDiff::class)]
#[UsesClass(InterfaceTypeDiff::class)]
#[UsesClass(FieldDiff::class)]
#[UsesClass(SchemaDiff::class)]
#[UsesClass(ValueDiff::class)]
#[UsesClass(ArgumentDiff::class)]
#[Group('graphql')]
class SchemaComparatorTest extends TestCase
{
    private string $base_schema = '
        schema {
            query: Query
            mutation: Mutation
        }
        type Query {
            getApples: [Apple!]!
        }
        type Mutation {
            addApple(apple: AppleInput!): Apple!
        }
        type Apple implements Fruit {
            id: ID!
            type: AppleType!
        }
        input AppleInput {
            type: AppleType!
        }
        interface Fruit {
            id: ID!
        }
        enum AppleType {
            MACINTOSH
            ROYAL_GALA
        }
    ';

    #[Test]
    public function compareSchemas(): void
    {
        $old_schema = BuildSchema::build($this->base_schema);
        $new_schema = BuildSchema::build('
            schema {
              query: Query
            }
            
            type Query {
              getApples: [Apple!]!
            }
            
            type Apple {
              id: ID!
              type: AppleType
            }

            enum AppleType {
               MACINTOSH
               ROYAL_GALA
            }
        ');

        $diff = SchemaComparator::compareSchemas($old_schema, $new_schema);
        $this->assertTrue($diff->isDifferent());
    }

    #[Test]
    public function addedObjectType(): void
    {
        $old = BuildSchema::build($this->base_schema);
        $new = BuildSchema::build('
            schema {
                query: Query
                mutation: Mutation
            }
            type Query {
                getApples: [Apple!]!
                getBaskets: [Basket!]!
            }
            type Mutation {
                addApple(apple: AppleInput!): Apple!
            }
            type Basket {
                items: [Fruit!]!
            }
            type Apple implements Fruit {
                id: ID!
                type: AppleType!
            }
            input AppleInput {
                type: AppleType!
            }
            interface Fruit {
                id: ID!
            }
            enum AppleType {
                MACINTOSH
                ROYAL_GALA
            }
        ');
        $diff = SchemaComparator::compareSchemas($old, $new);
        $this->assertTrue($diff->isDifferent());
        $this->assertNotEmpty($diff->getNewTypes());
        $this->assertNotEmpty($diff->getAlteredTypes());
        $this->assertEmpty($diff->getDroppedTypes());

        // Ensure that the only new type is the basket type.
        foreach ($diff->getNewTypes() as $type) {
            $this->assertEquals('Basket', $type->name);
        }

        // Double check that only the query type was updated.
        foreach ($diff->getAlteredTypes() as $type) {
            $this->assertEquals('Query', $type->getOldType()->name);
        }
    }

    #[Test]
    public function removedObjectType(): void
    {
        $new = BuildSchema::build('
            schema {
                query: Query
                mutation: Mutation
            }
            type Query {
                getApples: [Apple!]!
            }
            type Mutation {
                addApple(apple: AppleInput!): Apple!
            }
            type Apple {
                type: String!
            }
            input AppleInput {
                type: AppleType!
            }
            interface Fruit {
                id: ID!
            }
            enum AppleType {
                MACINTOSH
                ROYAL_GALA
            }
        ');
        $old = BuildSchema::build($this->base_schema);

        $diff = SchemaComparator::compareSchemas($old, $new);
        $this->assertTrue($diff->isDifferent());
    }
}
