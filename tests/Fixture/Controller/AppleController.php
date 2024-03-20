<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\Tests\Fixture\Entity\Apple;
use ForestCityLabs\Framework\Tests\Fixture\Entity\AppleTypeEnum;
use Ramsey\Uuid\UuidInterface;

class AppleController
{
    #[GraphQL\Query]
    #[GraphQL\Field(type: Apple::class)]
    public function getApples(
        EntityManagerInterface $em
    ): array {
        return $em->getRepository(Apple::class)->findAll();
    }

    #[GraphQL\Query]
    #[GraphQL\Field]
    public function getApple(
        #[GraphQL\Argument] UuidInterface $id,
        EntityManagerInterface $em
    ): ?Apple {
        return $em->getRepository(Apple::class)->findOneBy(['id' => $id]);
    }

    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function addApple(
        #[GraphQL\Argument] Apple $apple,
        EntityManagerInterface $em
    ): Apple {
        $em->persist($apple);
        $em->flush();
        return $apple;
    }

    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function updateApple(
        #[GraphQL\Argument] UuidInterface $id,
        #[GraphQL\Argument] AppleTypeEnum $type,
        EntityManagerInterface $em
    ): Apple {
        $apple = $em->getRepository(Apple::class)->findOneBy(['id' => $id]);
        $apple->setType($type);
        $em->flush();
        return $apple;
    }

    #[GraphQL\Mutation]
    #[GraphQL\Field]
    public function removeApple(
        #[GraphQL\Argument] UuidInterface $id,
        EntityManagerInterface $em
    ): UuidInterface {
        $em->remove($em->getRepository(Apple::class)->findOneBy(['id' => $id]));
        $em->flush();
        return $id;
    }
}
