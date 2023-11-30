<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Session\Driver;

use ForestCityLabs\Framework\Session\Session;
use ForestCityLabs\Framework\Session\SessionDriverInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FileAttributes;
use Ramsey\Uuid\UuidInterface;

class FilesystemSessionDriver implements SessionDriverInterface
{
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function load(UuidInterface $uuid): ?Session
    {
        $filename = $this->determineFilename($uuid);
        if (!$this->filesystem->has($filename)) {
            return null;
        }
        $session = unserialize($this->filesystem->read($filename));
        return $session;
    }

    public function save(Session $session): void
    {
        $filename = $this->determineFilename($session->getId());
        if ($this->filesystem->has($filename)) {
            $this->filesystem->delete($filename);
        }
        $this->filesystem->write($filename, serialize($session));
    }

    public function delete(Session $session): void
    {
        $filename = $this->determineFilename($session->getId());
        $this->filesystem->delete($filename);
    }

    public function deleteAll(): void
    {
        foreach ($this->filesystem->listContents('/', true) as $file) {
            if ($file instanceof FileAttributes) {
                $this->filesystem->delete($file->path());
            }
        }
    }

    public function loadAll(): iterable
    {
        foreach ($this->filesystem->listContents('/', true) as $file) {
            if ($file instanceof FileAttributes) {
                yield unserialize($this->filesystem->read($file->path()));
            }
        }
    }

    private function determineFilename(UuidInterface $uuid): string
    {
        return str_replace('-', DIRECTORY_SEPARATOR, $uuid->toString());
    }
}
