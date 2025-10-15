<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Middleware;

use ForestCityLabs\Framework\Http\Message\PartialStream;
use ForestCityLabs\Framework\Middleware\RangeMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(RangeMiddleware::class)]
#[UsesClass(PartialStream::class)]
class RangeMiddlewareTest extends TestCase
{
    private function createTestStream(string $content): Stream
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);
        return new Stream($resource);
    }

    private function createLogger(): LoggerInterface
    {
        return $this->createStub(LoggerInterface::class);
    }

    private function createRangeResponse(int $status, array $headers, Stream $body): ResponseInterface
    {
        // Add Content-Type header if not present (default to video/mp4 for testing)
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'video/mp4';
        }
        return new Response($status, $headers, $body);
    }

    #[Test]
    public function noRangeHeaderReturnsFullResponse(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('Hello World');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt');
        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Hello World', (string) $result->getBody());
        $this->assertEquals('bytes', $result->getHeaderLine('Accept-Ranges'));
    }

    #[Test]
    public function simpleRangeRequest(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=0-4']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 0-4/10', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('5', $result->getHeaderLine('Content-Length'));
        $this->assertEquals('01234', (string) $result->getBody());
        $this->assertInstanceOf(PartialStream::class, $result->getBody());
    }

    #[Test]
    public function rangeFromMiddle(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=3-7']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 3-7/10', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('5', $result->getHeaderLine('Content-Length'));
        $this->assertEquals('34567', (string) $result->getBody());
    }

    #[Test]
    public function openEndedRange(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=7-']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 7-9/10', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('3', $result->getHeaderLine('Content-Length'));
        $this->assertEquals('789', (string) $result->getBody());
    }

    #[Test]
    public function suffixRange(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        // Last 3 bytes
        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=-3']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 7-9/10', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('3', $result->getHeaderLine('Content-Length'));
        $this->assertEquals('789', (string) $result->getBody());
    }

    #[Test]
    public function suffixRangeLargerThanFile(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        // Request last 100 bytes of a 10-byte file
        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=-100']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 0-9/10', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('10', $result->getHeaderLine('Content-Length'));
        $this->assertEquals('0123456789', (string) $result->getBody());
    }

    #[Test]
    public function rangeOutOfBoundsReturns416(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=20-30']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(416, $result->getStatusCode());
        $this->assertEquals('bytes */10', $result->getHeaderLine('Content-Range'));
    }

    #[Test]
    public function invalidRangeFormatReturns416(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=invalid']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(416, $result->getStatusCode());
        $this->assertEquals('bytes */10', $result->getHeaderLine('Content-Range'));
    }

    #[Test]
    public function invalidRangeUnitReturns416(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'items=0-5']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(416, $result->getStatusCode());
    }

    #[Test]
    public function endBeforeStartReturns416(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=5-2']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(416, $result->getStatusCode());
    }

    #[Test]
    public function rangeExceedingFileSizeIsClamped(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=5-100']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 5-9/10', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('5', $result->getHeaderLine('Content-Length'));
        $this->assertEquals('56789', (string) $result->getBody());
    }

    #[Test]
    public function nonSuccessResponseIsNotProcessed(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $response = new Response(404, [], 'Not Found');

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=0-5']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('Not Found', (string) $result->getBody());
    }

    #[Test]
    public function streamWithoutSizeReturnsFullResponse(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        // Create a stream that doesn't report its size
        $stream = $this->createMock(Stream::class);
        $stream->method('getSize')->willReturn(null);
        $stream->method('__toString')->willReturn('content');

        $response = new Response(200, [], $stream);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=0-5']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function acceptRangesHeaderIsAdded(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('content');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt');
        $result = $middleware->process($request, $handler);

        $this->assertTrue($result->hasHeader('Accept-Ranges'));
        $this->assertEquals('bytes', $result->getHeaderLine('Accept-Ranges'));
    }

    #[Test]
    public function existingAcceptRangesHeaderIsPreserved(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('content');
        $response = new Response(200, [
            'Accept-Ranges' => 'none',
            'Content-Type' => 'video/mp4'
        ], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt');
        $result = $middleware->process($request, $handler);

        $this->assertEquals('none', $result->getHeaderLine('Accept-Ranges'));
    }

    #[Test]
    public function multipleRangesReturnsFullResponse(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        // Multiple ranges not supported yet, should return full response
        $request = new ServerRequest('GET', '/file.txt', ['Range' => 'bytes=0-2,5-7']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('0123456789', (string) $result->getBody());
    }

    #[Test]
    public function ifRangeWithMatchingETag(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $etag = '"abc123"';
        $response = new Response(200, [
            'ETag' => $etag,
            'Content-Type' => 'video/mp4'
        ], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', [
            'Range' => 'bytes=0-4',
            'If-Range' => $etag
        ]);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('01234', (string) $result->getBody());
    }

    #[Test]
    public function ifRangeWithNonMatchingETag(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = new Response(200, [
            'ETag' => '"abc123"',
            'Content-Type' => 'video/mp4'
        ], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/file.txt', [
            'Range' => 'bytes=0-4',
            'If-Range' => '"different"'
        ]);
        $result = $middleware->process($request, $handler);

        // Should return full response when ETag doesn't match
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('0123456789', (string) $result->getBody());
    }

    #[Test]
    public function ifRangeWithMatchingDate(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $lastModified = 'Wed, 21 Oct 2015 07:28:00 GMT';
        $response = new Response(200, [
            'Last-Modified' => $lastModified,
            'Content-Type' => 'video/mp4'
        ], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        // If-Range date is after Last-Modified, so range should be processed
        $request = new ServerRequest('GET', '/file.txt', [
            'Range' => 'bytes=0-4',
            'If-Range' => 'Wed, 21 Oct 2015 08:00:00 GMT'
        ]);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('01234', (string) $result->getBody());
    }

    #[Test]
    public function ifRangeWithOlderDate(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $lastModified = 'Wed, 21 Oct 2015 07:28:00 GMT';
        $response = new Response(200, [
            'Last-Modified' => $lastModified,
            'Content-Type' => 'video/mp4'
        ], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        // If-Range date is before Last-Modified, so full response should be returned
        $request = new ServerRequest('GET', '/file.txt', [
            'Range' => 'bytes=0-4',
            'If-Range' => 'Wed, 21 Oct 2015 07:00:00 GMT'
        ]);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('0123456789', (string) $result->getBody());
    }

    #[Test]
    public function largeFileRangeRequest(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        // Create a larger content to test chunked reading
        $content = str_repeat('A', 10000);
        $body = $this->createTestStream($content);
        $response = $this->createRangeResponse(200, [], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/large.bin', ['Range' => 'bytes=5000-5999']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('bytes 5000-5999/10000', $result->getHeaderLine('Content-Range'));
        $this->assertEquals('1000', $result->getHeaderLine('Content-Length'));

        $body = (string) $result->getBody();
        $this->assertEquals(1000, strlen($body));
        $this->assertEquals(str_repeat('A', 1000), $body);
    }

    #[Test]
    public function unsupportedContentTypeReturnsFullResponse(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('{"data":"value"}');
        // JSON response should not support ranges
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/api/data', ['Range' => 'bytes=0-4']);
        $result = $middleware->process($request, $handler);

        // Should return full response (200) without processing range
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('{"data":"value"}', (string) $result->getBody());
        $this->assertFalse($result->hasHeader('Content-Range'));
    }

    #[Test]
    public function htmlContentTypeReturnsFullResponse(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('<html><body>Hello</body></html>');
        // HTML response should not support ranges
        $response = new Response(200, ['Content-Type' => 'text/html'], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/page.html', ['Range' => 'bytes=0-10']);
        $result = $middleware->process($request, $handler);

        // Should return full response (200) without processing range
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('<html><body>Hello</body></html>', (string) $result->getBody());
        $this->assertFalse($result->hasHeader('Content-Range'));
    }

    #[Test]
    public function audioContentTypeSupportsRanges(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = new Response(200, ['Content-Type' => 'audio/mpeg'], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/song.mp3', ['Range' => 'bytes=0-4']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('01234', (string) $result->getBody());
    }

    #[Test]
    public function pdfContentTypeSupportsRanges(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        $response = new Response(200, ['Content-Type' => 'application/pdf'], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/document.pdf', ['Range' => 'bytes=0-4']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('01234', (string) $result->getBody());
    }

    #[Test]
    public function contentTypeWithCharsetSupportsRanges(): void
    {
        $middleware = new RangeMiddleware($this->createLogger());

        $body = $this->createTestStream('0123456789');
        // Content-Type with charset parameter
        $response = new Response(200, ['Content-Type' => 'video/mp4; charset=utf-8'], $body);

        $handler = $this->createConfiguredStub(RequestHandlerInterface::class, [
            'handle' => $response
        ]);

        $request = new ServerRequest('GET', '/video.mp4', ['Range' => 'bytes=0-4']);
        $result = $middleware->process($request, $handler);

        $this->assertEquals(206, $result->getStatusCode());
        $this->assertEquals('01234', (string) $result->getBody());
    }
}
