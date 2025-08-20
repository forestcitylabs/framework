<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Security\Model;

use DateTimeImmutable;

interface AuthCodeInterface
{
    /**
     * Get the authorization code.
     *
     * @return string The authorization code.
     */
    public function getCode(): string;

    /**
     * Set the authorization code.
     *
     * @param string $code The authorization code.
     */
    public function setCode(string $code): void;

    /**
     * Get the client associated with the authorization code.
     *
     * @return ClientInterface The client.
     */
    public function getClient(): ClientInterface;

    /**
     * Set the client associated with the authorization code.
     *
     * @param ClientInterface $client The client to associate with the authorization code.
     */
    public function setClient(ClientInterface $client): void;

    /**
     * Get the scopes associated with the authorization code.
     *
     * @return array<string> The scopes.
     */
    public function getScopes(): array;

    /**
     * Add a scope to the authorization code.
     *
     * @param string $scope The scope to add.
     */
    public function addScope(string $scope): void;

    /**
     * Get the expiration time of the authorization code.
     *
     * @return \DateTimeInterface The expiration time.
     */
    public function getExpiresAt(): DateTimeImmutable;

    /**
     * Set the expiration time of the authorization code.
     *
     * @param \DateTimeInterface $expiresAt The expiration time.
     */
    public function setExpiresAt(DateTimeImmutable $expiresAt): void;

    public function getUser(): UserInterface;

    public function setUser(UserInterface $user): void;
}
