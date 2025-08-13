<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\OAuth;

use DateTimeImmutable;

class AuthRequest
{
    public function __construct(
        private string $client_id,
        private string $redirect_uri,
        private string $response_type,
        private DateTimeImmutable $expires_at,
        private ?string $scope = null,
        private ?string $state = null,
        private ?string $code_challenge = null,
        private ?string $code_challenge_method = null,
        private ?string $nonce = null,
    ) {
    }

    public function getClientId(): string
    {
        return $this->client_id;
    }

    public function setClientId(string $client_id): void
    {
        $this->client_id = $client_id;
    }

    public function getRedirectUri(): string
    {
        return $this->redirect_uri;
    }

    public function setRedirectUri(string $redirect_uri): void
    {
        $this->redirect_uri = $redirect_uri;
    }

    public function getResponseType(): string
    {
        return $this->response_type;
    }

    public function setResponseType(string $response_type): void
    {
        $this->response_type = $response_type;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpiresAt(DateTimeImmutable $expires_at): void
    {
        $this->expires_at = $expires_at;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getCodeChallenge(): ?string
    {
        return $this->code_challenge;
    }

    public function setCodeChallenge(?string $code_challenge): void
    {
        $this->code_challenge = $code_challenge;
    }

    public function getCodeChallengeMethod(): ?string
    {
        return $this->code_challenge_method;
    }

    public function setCodeChallengeMethod(?string $code_challenge_method): void
    {
        $this->code_challenge_method = $code_challenge_method;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setNonce(?string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function __serialize(): array
    {
        return [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => $this->response_type,
            'expires_at' => $this->expires_at,
            'scope' => $this->scope,
            'state' => $this->state,
            'code_challenge' => $this->code_challenge,
            'code_challenge_method' => $this->code_challenge_method,
            'nonce' => $this->nonce,
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => $this->response_type,
            'expires_at' => $this->expires_at,
            'scope' => $this->scope,
            'state' => $this->state,
            'code_challenge' => $this->code_challenge,
            'code_challenge_method' => $this->code_challenge_method,
            'nonce' => $this->nonce,
        ] = $serialized;
    }
}
