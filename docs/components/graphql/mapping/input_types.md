Input Types
===========

Definition
----------

Input types can easily be defined _on top_ of object types by defining arguments on the same properties as fields.

```php
<?php

namespace Application\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\ObjectType]
#[GraphQL\InputType(name: "AppleInput")]
class Apple
{
    #[GraphQL\Field]
    private UuidInterface $uuid;

    #[GraphQL\Field]
    #[GraphQL\Argument]
    private string $type;

    #[GraphQL\Field]
    #[GraphQL\Argument]
    private ?string $name;
}
```

!!! note

    If you do not set a custom `name` attribute for the input type in the above example it will select "Apple" by default and override the "Apple" object type.

Our schema for apples then becomes:

```graphql
type Apple {
    uuid: ID!
    type: String!
    name: String
}

input AppleInput {
    type: String!
    name: String
}
```

Usage
-----

Input types can be used in controllers (and nested within one another). Values passed to the API endpoint will be automatically transformed to their representative class before being passed into the controller method, for example:

```php title="src/Entity/AppleController.php"
<?php

namespace Application\Controller;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

class AppleController
{
    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function addApple(
        #[GraphQL\Argument] Apple $apple
    ): Apple {
        // ...do something to persist the apple.
        return $apple;
    }
}
```

```graphql
type Mutation {
    addApple(apple: AppleInput!): Apple
}
```
