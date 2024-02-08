<?php

namespace ForestCityLabs\Framework\Utility\ORM;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Psr\Container\ContainerInterface;

class EntityListenerResolver extends DefaultEntityListenerResolver
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function resolve(string $className): object
    {
        return $this->container->get($className);
    }
}
