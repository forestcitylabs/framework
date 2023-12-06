<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Cache;

use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    public function __construct(
        private string $key,
        private mixed $value = null,
        private ?DateTimeInterface $expires = null,
        private bool $hit = false
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->hit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expires = $expiration;
        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expires = null;
            return $this;
        }

        $now = new \DateTime('now');
        if (is_int($time)) {
            $interval = new \DateInterval('PT' . $time);
        } else {
            $interval = $time;
        }

        $now->add($interval);
        $this->expires = \DateTimeImmutable::createFromMutable($now);
        return $this;
    }

    public function getExpires(): ?DateTimeInterface
    {
        return $this->expires;
    }

    public function __serialize(): array
    {
        return [
            'key' => $this->key,
            'expires' => ($this->expires === null) ? null : $this->expires->format('c'),
            'value' => serialize($this->value),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->key = $data['key'];
        $this->expires = $data['expires'] === null ? null : new \DateTimeImmutable($data['expires']);
        $this->value = unserialize($data['value']);
        $this->hit = true;
    }
}
