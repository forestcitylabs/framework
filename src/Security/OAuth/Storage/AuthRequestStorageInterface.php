<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth\Storage;

use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AuthRequestStorageInterface
{
    public function storeAuthRequest(ServerRequestInterface $request, AuthRequest $auth_request): ResponseInterface;
    public function getAuthRequest(ServerRequestInterface $request): ?AuthRequest;
    public function removeAuthRequest(ServerRequestInterface $request): ResponseInterface;
}
