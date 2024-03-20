Object Types
============

Object types are usually the first thing you map in building out your schema. You will typically use entities in your system to construct objects.

```php
<?php

namespace Application\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\ObjectType]
class Apple
{
    #[GraphQL\Field]
    private UuidInterface $uuid;

    #[GraphQL\Field]
    private string $type;

    #[GraphQL\Field]
    private ?string $name;
}
```
!!! note

    You can set custom properties like `name`, `description`, `type`, `list`, `not_null` and `deprecation_reason` on attributes, but reasonable defaults will be chosen if you do not do this.


The above entity would be represented as such in your schema:

```graphql
type Apple {
    uuid: ID!
    type: String!
    name: String
}
```

## Annotations

Let's take a moment to analyze the attributes we used to define our schema above.

### `ObjectType`

The object type annotation tells our schema that this is a custom object type. You can define a custom name, if none is provided then it will use the class name ("Apple" in this case). You can also provide a description that will help users of the API understand your type.

### `Field`

The field attribute allows you to define fields on your custom type. Reasonable defaults will be selected for `name`, `type`, `list` and `not_null` if possible.
