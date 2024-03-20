Interface Types
===============

Interface types are defined natively using php interfaces, abstract classes or classes. Once defined you simply need to define an object type that extends that interface and it will be mapped automatically.

```php title="src/Entity/FruitInterface.php"
<?php

namespace Application\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\InterfaceType]
interface FruitInterface
{
    #[GraphQL\Field(name: 'region')]
    public function getRegion(): string;
}
```

```php title="src/Entity/Apple.php"
<?php

namespace Application\Entity;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;

#[GraphQL\ObjectType]
class Apple implements FruitInterface
{
    #[GraphQL\Field]
    private UuidInterface $uuid;

    #[GraphQL\Field]
    private string $region;

    public function getRegion(): string
    {
        return $this->region;
    }
}
```

These definitions would result in the following schema:

```graphql title="schema.graphql"
interface Fruit {
    region: String!
}

type Apple implements Fruit {
    uuid: ID!
    region: String!
}
```
