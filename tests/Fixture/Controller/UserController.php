<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Fixture\Controller;

use Application\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ForestCityLabs\Framework\Routing\Attribute\Route;
use ForestCityLabs\Framework\Routing\Attribute\RoutePrefix;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

#[RoutePrefix("/user")]
class UserController
{
    public function __construct(
        private ResponseFactoryInterface $response_factory
    ) {
    }

    #[Route("/login")]
    public function login(): ResponseInterface
    {
        return $this->response_factory->createResponse();
    }

    #[Route("/logout")]
    public function logout(): ResponseInterface
    {
        return $this->response_factory->createResponse();
    }

    #[Route("/created/{since}")]
    public function createdSince(
        DateTime $since,
        EntityManagerInterface $em
    ): ResponseInterface {
        $em->getRepository(User::class)->findBy(['created' => $since]);
        return $this->response_factory->createResponse();
    }

    #[Route("/active")]
    public function getActive(
        EntityManagerInterface $em,
        bool $active = true
    ): ResponseInterface {
        $em->getRepository(User::class)->findBy(['active' => $active]);
        return $this->response_factory->createResponse();
    }
}
