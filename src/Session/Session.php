<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Session;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Session
{
    private DateTimeInterface $expiry;
    private array $data = [];

    public function __construct(
        private UuidInterface $id
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getExpiry(): DateTimeInterface
    {
        return $this->expiry;
    }

    public function setExpiry(DateTimeInterface $expiry): self
    {
        $this->expiry = $expiry;

        return $this;
    }

    public function hasValue(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function getValue(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function setValue(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function removeValue(string $key): mixed
    {
        $value = $this->data[$key] ?? null;
        unset($this->data[$key]);

        return $value;
    }

    public function isEmpty(): bool
    {
        return (bool) (0 == count($this->data));
    }

    public static function fromRequest(ServerRequestInterface $request): ?Session
    {
        if (null === $session = $request->getAttribute('_session')) {
            return null;
        }

        if ($session instanceof Session) {
            return $session;
        }

        throw new LogicException('Invalid session');
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id->toString(),
            'expiry' => $this->expiry->format('c'),
            'data' => serialize($this->data),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = Uuid::fromString($data['id']);
        $this->expiry = new DateTimeImmutable($data['expiry']);
        $this->data = unserialize($data['data']);
    }
}
