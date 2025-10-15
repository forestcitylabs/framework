<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Http\Message;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * A stream decorator that exposes only a specific byte range of an underlying stream.
 *
 * This is useful for HTTP range requests where you want to stream only part of a file
 * without copying data to a new stream. The underlying stream is read progressively
 * as data is requested, making this memory-efficient for large files.
 */
class PartialStream implements StreamInterface
{
    private int $position = 0;
    private bool $initialSeekDone = false;

    /**
     * @param StreamInterface $stream The underlying stream to read from
     * @param int $start The starting byte position (inclusive)
     * @param int $length The number of bytes to expose
     * @throws RuntimeException If the stream is not seekable and start is not 0
     */
    public function __construct(
        private StreamInterface $stream,
        private int $start,
        private int $length
    ) {
        if ($start < 0) {
            throw new RuntimeException('Start position must be >= 0');
        }

        if ($length < 0) {
            throw new RuntimeException('Length must be >= 0');
        }

        // If we need to start at a non-zero position, the stream must be seekable
        if ($start > 0 && !$stream->isSeekable()) {
            throw new RuntimeException('Cannot create partial stream with non-zero start position on non-seekable stream');
        }
    }

    public function __toString(): string
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function detach()
    {
        $this->position = 0;
        $this->initialSeekDone = false;
        $this->length = 0;
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        // If stream is detached, return null
        if ($this->stream->getSize() === null) {
            return null;
        }
        return $this->length;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= $this->length || $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        // Calculate the new position relative to our partial stream
        $newPosition = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->length + $offset,
            default => throw new RuntimeException('Invalid whence value'),
        };

        if ($newPosition < 0) {
            throw new RuntimeException('Cannot seek to negative position');
        }

        if ($newPosition > $this->length) {
            throw new RuntimeException('Cannot seek beyond end of partial stream');
        }

        // Seek in the underlying stream to the absolute position
        $this->stream->seek($this->start + $newPosition);
        $this->position = $newPosition;
        $this->initialSeekDone = true;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false; // Partial streams are read-only
    }

    public function write(string $string): int
    {
        throw new RuntimeException('Cannot write to a partial stream');
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function read(int $length): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        // Perform initial seek if not done yet
        if (!$this->initialSeekDone && $this->start > 0) {
            $this->seek(0);
        }

        // Calculate how many bytes we can actually read
        $remaining = $this->length - $this->position;
        $toRead = min($length, $remaining);

        if ($toRead <= 0) {
            return '';
        }

        $data = $this->stream->read($toRead);
        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        // Perform initial seek if not done yet
        if (!$this->initialSeekDone && $this->start > 0) {
            $this->seek(0);
        }

        $remaining = $this->length - $this->position;
        if ($remaining <= 0) {
            return '';
        }

        // Read all remaining bytes in chunks to avoid memory issues
        $buffer = '';
        while ($remaining > 0 && !$this->eof()) {
            $chunk = $this->read(min(8192, $remaining));
            if ($chunk === '') {
                break;
            }
            $buffer .= $chunk;
            $remaining = $this->length - $this->position;
        }

        return $buffer;
    }

    public function getMetadata(?string $key = null)
    {
        $metadata = $this->stream->getMetadata($key);

        // If getting all metadata, add our partial stream info
        if ($key === null && is_array($metadata)) {
            $metadata['partial_stream'] = [
                'start' => $this->start,
                'length' => $this->length,
                'position' => $this->position,
            ];
        }

        return $metadata;
    }
}
