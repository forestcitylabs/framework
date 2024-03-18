<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Basket;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use Ramsey\Uuid\UuidInterface;

class BasketController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[GraphQL\Query]
    #[GraphQL\Field(type: 'Basket')]
    public function getBaskets(): array
    {
        return $this->em->getRepository(Basket::class)->findAll();
    }

    #[GraphQL\Query]
    #[GraphQL\Field]
    public function getBasket(
        #[GraphQL\Argument] UuidInterface $id
    ): ?Basket {
        return $this->em->getRepository(Basket::class)->findOneBy(['id' => $id]);
    }

    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function createBasket(
        #[GraphQL\Argument] Basket $basket
    ): Basket {
        $this->em->persist($basket);
        $this->em->flush();
        return $basket;
    }

    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function removeBasket(
        #[GraphQL\Argument] UuidInterface $id
    ): UuidInterface {
        $this->em->remove($this->em->getRepository(Basket::class)->findOneBy(['id' => $id]));
        $this->em->flush();
        return $id;
    }

    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function addApple(
        #[GraphQL\Argument] UuidInterface $id,
        #[GraphQL\Argument] Apple $apple
    ): Basket {
        $this->em->persist($apple);
        $basket = $this->em->getRepository(Basket::class)->findOneBy(['id' => $id]);
        $basket->addApple($apple);
        $this->em->flush();
        return $basket;
    }
}
