<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Middleware;

use ForestCityLabs\Framework\Http\Message\PartialStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware that handles HTTP Range requests for partial content delivery.
 *
 * This middleware enables:
 * - Video/audio seeking in browsers
 * - Resumable downloads
 * - Efficient bandwidth usage for large files
 *
 * It processes Range headers and returns 206 Partial Content responses
 * using PartialStream for memory-efficient streaming.
 *
 * Range support is only enabled for specific content types (binary/streamable content)
 * to prevent range requests on dynamically generated content (like HTML/JSON)
 * where partial responses don't make sense.
 *
 * Supported content types:
 * - video/* (e.g., video/mp4, video/webm)
 * - audio/* (e.g., audio/mpeg, audio/ogg)
 * - application/pdf
 * - application/octet-stream
 * - image/* (e.g., image/jpeg, image/png)
 * - Various archive formats (zip, gzip, tar, etc.)
 */
class RangeMiddleware implements MiddlewareInterface
{
    /**
     * Content types that support range requests.
     */
    private const SUPPORTED_TYPES = [
        'video/',
        'audio/',
        'application/pdf',
        'application/octet-stream',
        'image/',
        'application/zip',
        'application/x-tar',
        'application/gzip',
        'application/x-gzip',
        'application/x-bzip2',
        'application/x-7z-compressed',
        'application/x-rar-compressed',
    ];

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Get the response from downstream
        $response = $handler->handle($request);

        // Only process successful responses with content
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        // Check if the content type supports range requests
        $contentType = $response->getHeaderLine('Content-Type');
        if (!$this->shouldSupportRanges($contentType)) {
            $this->logger->debug('Content type does not support ranges', [
                'class' => self::class,
                'content_type' => $contentType
            ]);
            return $response;
        }

        // Check if the response already indicates it accepts ranges
        if (!$response->hasHeader('Accept-Ranges')) {
            $response = $response->withHeader('Accept-Ranges', 'bytes');
        }

        // Check if this is a range request
        if (!$request->hasHeader('Range')) {
            $this->logger->debug('No Range header present, returning full response', ['class' => self::class]);
            return $response;
        }

        $body = $response->getBody();
        $size = $body->getSize();

        // If we can't determine the size, we can't process ranges
        if ($size === null) {
            $this->logger->warning('Cannot process range request: stream size unknown', ['class' => self::class]);
            return $response;
        }

        // Parse the range header
        $rangeHeader = $request->getHeaderLine('Range');
        $ranges = $this->parseRangeHeader($rangeHeader, $size);

        if ($ranges === null) {
            $this->logger->warning('Invalid Range header format', [
                'class' => self::class,
                'range' => $rangeHeader
            ]);
            // Return 416 Range Not Satisfiable
            return $response
                ->withStatus(416)
                ->withHeader('Content-Range', "bytes */$size");
        }

        if (empty($ranges)) {
            $this->logger->warning('Range request out of bounds', [
                'class' => self::class,
                'range' => $rangeHeader,
                'size' => $size
            ]);
            // Return 416 Range Not Satisfiable
            return $response
                ->withStatus(416)
                ->withHeader('Content-Range', "bytes */$size");
        }

        // Handle If-Range conditional requests
        if ($request->hasHeader('If-Range')) {
            if (!$this->validateIfRange($request, $response)) {
                $this->logger->debug('If-Range validation failed, returning full response', ['class' => self::class]);
                return $response;
            }
        }

        // For now, we only support single ranges (most common case)
        // Multipart ranges would require more complex handling
        if (count($ranges) > 1) {
            $this->logger->info('Multiple ranges requested, returning full response', [
                'class' => self::class,
                'range_count' => count($ranges)
            ]);
            return $response;
        }

        [$start, $end] = $ranges[0];
        $length = $end - $start + 1;

        $this->logger->debug('Processing range request', [
            'class' => self::class,
            'start' => $start,
            'end' => $end,
            'length' => $length,
            'total_size' => $size
        ]);

        // Create a partial stream
        $partialStream = new PartialStream($body, $start, $length);

        // Return 206 Partial Content response
        return $response
            ->withStatus(206)
            ->withHeader('Content-Range', "bytes $start-$end/$size")
            ->withHeader('Content-Length', (string) $length)
            ->withBody($partialStream);
    }

    /**
     * Parse Range header and return array of [start, end] pairs.
     *
     * @param string $rangeHeader The Range header value (e.g., "bytes=0-1023")
     * @param int $size The total size of the resource
     * @return array|null Array of [start, end] pairs, null if invalid format, empty if out of bounds
     */
    private function parseRangeHeader(string $rangeHeader, int $size): ?array
    {
        // Range header format: "bytes=start-end"
        if (!preg_match('/^bytes=(.+)$/', $rangeHeader, $matches)) {
            return null;
        }

        $rangeSpecs = explode(',', $matches[1]);
        $ranges = [];

        foreach ($rangeSpecs as $rangeSpec) {
            $rangeSpec = trim($rangeSpec);

            // Parse "start-end", "start-", or "-suffix"
            if (!preg_match('/^(\d*)-(\d*)$/', $rangeSpec, $parts)) {
                return null;
            }

            $start = $parts[1];
            $end = $parts[2];

            if ($start === '' && $end === '') {
                // Invalid: both empty
                return null;
            }

            if ($start === '') {
                // Suffix range: "-500" means last 500 bytes
                $suffixLength = (int) $end;
                if ($suffixLength === 0) {
                    continue; // Skip invalid
                }
                $start = max(0, $size - $suffixLength);
                $end = $size - 1;
            } elseif ($end === '') {
                // Open-ended range: "500-" means from byte 500 to end
                $start = (int) $start;
                $end = $size - 1;
            } else {
                // Complete range: "500-999"
                $start = (int) $start;
                $end = (int) $end;
            }

            // Validate range
            if ($start < 0 || $start >= $size || $end < $start) {
                continue; // Skip invalid ranges
            }

            // Clamp end to size
            $end = min($end, $size - 1);

            $ranges[] = [$start, $end];
        }

        return $ranges;
    }

    /**
     * Validate If-Range conditional header.
     *
     * If-Range allows the client to make a range request conditional on the
     * resource not having changed. It can contain either an ETag or a date.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     * @return bool True if the range request should proceed
     */
    private function validateIfRange(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        $ifRange = $request->getHeaderLine('If-Range');

        // Check if it's an ETag (starts with " or W/)
        if (str_starts_with($ifRange, '"') || str_starts_with($ifRange, 'W/')) {
            $etag = $response->getHeaderLine('ETag');
            return $etag !== '' && $etag === $ifRange;
        }

        // Otherwise treat it as a date
        $lastModified = $response->getHeaderLine('Last-Modified');
        if ($lastModified === '') {
            return false;
        }

        $ifRangeTime = strtotime($ifRange);
        $lastModifiedTime = strtotime($lastModified);

        if ($ifRangeTime === false || $lastModifiedTime === false) {
            return false;
        }

        // Resource must not have been modified since the If-Range date
        return $lastModifiedTime <= $ifRangeTime;
    }

    /**
     * Check if the content type should support range requests.
     *
     * @param string $contentType The Content-Type header value
     * @return bool True if ranges should be supported
     */
    private function shouldSupportRanges(string $contentType): bool
    {
        if (empty($contentType)) {
            return false;
        }

        // Extract the media type (without parameters like charset)
        $mediaType = explode(';', $contentType)[0];
        $mediaType = trim($mediaType);

        foreach (self::SUPPORTED_TYPES as $supportedType) {
            if (str_starts_with($mediaType, $supportedType)) {
                return true;
            }
        }

        return false;
    }
}
