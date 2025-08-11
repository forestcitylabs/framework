<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth\Grant;

use ForestCityLabs\Framework\Security\Model\AuthCodeInterface;
use ForestCityLabs\Framework\Security\Model\UserInterface;
use ForestCityLabs\Framework\Security\OAuth\AuthRequest;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use Psr\Http\Message\ServerRequestInterface;

interface GrantInterface
{
    public function canHandleAuthorizationRequest(ServerRequestInterface $request): bool;

    public function handleAuthorizationRequest(ServerRequestInterface $request): AuthRequest;

    public function approveAuthorizationRequest(
        AuthRequest $auth_request,
        ServerRequestInterface $request,
        ?array $granted_scopes = null,
        ?UserInterface $user = null,
    ): AuthCodeInterface;

    public function canHandleTokenRequest(ServerRequestInterface $request): bool;

    public function handleTokenRequest(ServerRequestInterface $request, ?AuthRequest $auth_request): OAuthTokenResponse;
}
