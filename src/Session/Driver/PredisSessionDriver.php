<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Session\Driver;

use DateTimeImmutable;
use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
use Predis\ClientInterface;
use Ramsey\Uuid\UuidInterface;

class PredisSessionDriver implements SessionDriverInterface
{
    public function __construct(
        private ClientInterface $redis
    ) {
    }

    public function load(UuidInterface $uuid): ?Session
    {
        if (null !== $data = $this->redis->get($uuid->toString())) {
            return unserialize($data);
        }

        return null;
    }

    public function loadAll(): iterable
    {
        foreach ($this->redis->keys('*') as $key) {
            yield unserialize($this->redis->get($key));
        }
    }

    public function save(Session $session): void
    {
        $expiry = $session->getExpiry()->getTimestamp() - (new DateTimeImmutable())->getTimestamp();
        $this->redis->setex(
            $session->getId()->toString(),
            ($expiry > 0) ? $expiry : null,
            serialize($session)
        );
    }

    public function delete(Session $session): void
    {
        $this->redis->del($session->getId()->toString());
    }

    public function deleteAll(): void
    {
        $this->redis->del($this->redis->keys('*'));
    }
}
