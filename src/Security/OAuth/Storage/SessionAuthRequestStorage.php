<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth\Storage;

use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SessionAuthRequestStorage implements AuthRequestStorageInterface
{
    public const SESSION_VALUE_KEY = '_oauth_auth_request';

    public function __construct(
        private ResponseFactoryInterface $rf
    ) {
    }

    public function getAuthRequest(ServerRequestInterface $request): ?AuthRequest
    {
        $session = $request->getAttribute('_session');
        return $session->getValue(self::SESSION_VALUE_KEY);
    }

    public function storeAuthRequest(ServerRequestInterface $request, AuthRequest $auth_request): ResponseInterface
    {
        $session = $request->getAttribute('_session');
        $session->setValue(self::SESSION_VALUE_KEY, $auth_request);
        return $this->rf->createResponse();
    }

    public function removeAuthRequest(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('_session');
        $session->removeValue(self::SESSION_VALUE_KEY);
        return $this->rf->createResponse();
    }
}
