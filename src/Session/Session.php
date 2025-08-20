<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Session;

use Psr\Http\Message\ServerRequestInterface;

class Session
{
    private $dirty = false;

    public function __construct(
        private array $data = [],
    ) {
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
        $this->dirty = true;
        $this->data[$key] = $value;

        return $this;
    }

    public function removeValue(string $key): mixed
    {
        $this->dirty = true;
        $value = $this->data[$key] ?? null;
        unset($this->data[$key]);

        return $value;
    }

    public function isEmpty(): bool
    {
        return (bool) (0 == count($this->data));
    }

    public function clear(): void
    {
        $this->dirty = true;
        $this->data = [];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public static function fromRequest(ServerRequestInterface $request): ?self
    {
        $session = $request->getAttribute('_session');
        if (null === $session) {
            return null;
        }

        assert($session instanceof self);

        return $session;
    }
}
