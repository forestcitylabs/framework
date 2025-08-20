<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth;

use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;

class OAuthTokenResponse
{
    private AccessTokenInterface $access_token;
    private ?RefreshTokenInterface $refresh_token = null;

    public function __construct(
        AccessTokenInterface $access_token,
        ?RefreshTokenInterface $refresh_token = null,
    ) {
        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
    }

    public function getAccessToken(): AccessTokenInterface
    {
        return $this->access_token;
    }

    public function getRefreshToken(): ?RefreshTokenInterface
    {
        return $this->refresh_token;
    }

    public function formatResponse(): array
    {
        return [
            'access_token' => $this->access_token->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $this->access_token->getExpiresAt()->getTimestamp() - time(),
            'refresh_token' => $this->refresh_token?->getToken(),
            'scope' => implode(' ', $this->access_token->getScopes()),
        ];
    }
}
