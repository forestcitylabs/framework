<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Oidc;

use ForestCityLabs\Framework\Security\Model\AccessTokenInterface;
use ForestCityLabs\Framework\Security\Model\RefreshTokenInterface;
use ForestCityLabs\Framework\Security\OAuth\OAuthTokenResponse;
use Lcobucci\JWT\Token;

class OidcTokenResponse extends OAuthTokenResponse
{
    private Token $id_token;

    public function __construct(
        AccessTokenInterface $access_token,
        ?RefreshTokenInterface $refresh_token = null,
        ?Token $id_token = null,
    ) {
        $this->id_token = $id_token;
        parent::__construct($access_token, $refresh_token);
    }

    public function getIdToken(): ?Token
    {
        return $this->id_token;
    }

    public function formatResponse(): array
    {
        $response = parent::formatResponse();
        if ($this->id_token) {
            $response['id_token'] = $this->id_token->toString();
        }
        return $response;
    }
}
