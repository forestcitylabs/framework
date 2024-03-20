Enum Types
==========

Definition
----------

Enum types are defined as PHP enums.

```php title="src/Entity/AppleTypeEnum.php"
<?php

namespace Application\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\EnumType]
enum AppleTypeEnum
{
    #[GraphQL\Value]
    case MACINTOSH;

    #[GraphQL\Value]
    case GREEN_DELICIOUS;

    #[GraphQL\Value]
    case ROYAL_GALA;
}
```
Would result in the following schema:

```graphql
enum AppleTypeEnum {
    MACINTOSH
    GREEN_DELICIOUS
    ROYAL_GALA
}
```

Usage
-----

Enums are unique as they can be used as both fields (output) and arguments (input), so when a return type or argument type is set to a mapped php enum it will be mapped in the schema as such.

### Field Example

```php title="src/Entity/Apple.php"
<?php

namespace Application\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\ObjectType]
class Apple
{
    #[GraphQL\Field]
    private UuidInterface $id;

    #[GraphQL\Field]
    private AppleTypeEnum $type;
}
```

```graphql
type Apple {
    id: ID!
    type: AppleTypeEnum!
}
```

### Argument Example

```php title="src/Controller/AppleController.php"
<?php

namespace Application\Controller;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

class AppleController
{
    #[GraphQL\Query]
    #[GraphQL\Field(type: "Apple")]
    public function getApplesByType(
        #[GraphQL\Argument] AppleTypeEnum $type
    ): array {
        // ...do some logic based on the apple type enum.
        return [];
    }
}
```

```graphql
type Query {
    getApplesByType(type: AppleTypeEnum!): [Apple!]!
}
```
