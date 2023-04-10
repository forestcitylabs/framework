<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Session;

use Ramsey\Uuid\UuidInterface;

interface SessionDriverInterface
{
    /**
     * Given a UUID this should load the corresponding session.
     *
     * @param UuidInterface $uuid The UUID of the session to load.
     *
     * @return ?Session The session or null.
     */
    public function load(UuidInterface $uuid): ?Session;

    /**
     * Load all sessions available from the backend, including expired sessions.
     *
     * @return Session[] An array of sessions.
     */
    public function loadAll(): iterable;

    /**
     * Save a session to the backend datasource.
     *
     * @param Session $session The session to save.
     *
     * @throws SessionDriverSaveException If there was an issue with the operation.
     */
    public function save(Session $session): void;

    /**
     * Remove a session from the backend datasource.
     *
     * @param Session $session The session to delete.
     *
     * @throws SessionDriverDeleteException If there was an issue with the operation.
     */
    public function delete(Session $session): void;

    /**
     * Remove all sessions from the backend datasource.
     *
     * @throws SessionDriverDeleteException If there was an issue with the operation.
     */
    public function deleteAll(): void;
}
