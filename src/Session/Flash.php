<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Session;

class Flash
{
    private array $data = [];

    public function addFlash(string $type, string $message): void
    {
        $this->data[$type][] = $message;
    }

    public function getFlash(string $type): array
    {
        $messages = $this->data[$type] ?? [];
        unset($this->data[$type]);

        return $messages;
    }

    public function getAll(): array
    {
        $flashes = $this->data;
        $this->data = [];

        return $flashes;
    }

    public function isEmpty(): bool
    {
        return (bool) (count($this->data) === 0);
    }

    public function __serialize(): array
    {
        return $this->data;
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data;
    }
}
