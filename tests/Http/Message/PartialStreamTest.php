<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Http\Message;

use ForestCityLabs\Framework\Http\Message\PartialStream;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PartialStream::class)]
class PartialStreamTest extends TestCase
{
    private function createTestStream(string $content): Stream
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);
        return new Stream($resource);
    }

    #[Test]
    public function constructorWithValidParameters(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertInstanceOf(PartialStream::class, $partial);
    }

    #[Test]
    public function constructorThrowsOnNegativeStart(): void
    {
        $stream = $this->createTestStream('Hello World');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Start position must be >= 0');
        new PartialStream($stream, -1, 5);
    }

    #[Test]
    public function constructorThrowsOnNegativeLength(): void
    {
        $stream = $this->createTestStream('Hello World');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Length must be >= 0');
        new PartialStream($stream, 0, -1);
    }

    #[Test]
    public function readFromBeginning(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertEquals('Hello', $partial->read(5));
    }

    #[Test]
    public function readFromMiddle(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $this->assertEquals('World', $partial->read(5));
    }

    #[Test]
    public function readPartialChunk(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertEquals('Hel', $partial->read(3));
        $this->assertEquals('lo', $partial->read(3));
    }

    #[Test]
    public function readBeyondLength(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $data = $partial->read(10);
        $this->assertEquals('Hello', $data);
        $this->assertEquals(5, strlen($data));
    }

    #[Test]
    public function readAfterEof(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $partial->read(5);
        $this->assertEquals('', $partial->read(5));
    }

    #[Test]
    public function getSize(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $this->assertEquals(5, $partial->getSize());
    }

    #[Test]
    public function tell(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $this->assertEquals(0, $partial->tell());
        $partial->read(3);
        $this->assertEquals(3, $partial->tell());
        $partial->read(2);
        $this->assertEquals(5, $partial->tell());
    }

    #[Test]
    public function eof(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertFalse($partial->eof());
        $partial->read(5);
        $this->assertTrue($partial->eof());
    }

    #[Test]
    public function isSeekable(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertTrue($partial->isSeekable());
    }

    #[Test]
    public function seekSet(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->seek(2);
        $this->assertEquals(2, $partial->tell());
        $this->assertEquals('rld', $partial->read(3));
    }

    #[Test]
    public function seekCur(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->read(2);
        $partial->seek(1, SEEK_CUR);
        $this->assertEquals(3, $partial->tell());
        $this->assertEquals('ld', $partial->read(2));
    }

    #[Test]
    public function seekEnd(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->seek(-2, SEEK_END);
        $this->assertEquals(3, $partial->tell());
        $this->assertEquals('ld', $partial->read(2));
    }

    #[Test]
    public function seekThrowsOnNegativePosition(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot seek to negative position');
        $partial->seek(-1);
    }

    #[Test]
    public function seekThrowsOnPositionBeyondEnd(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot seek beyond end of partial stream');
        $partial->seek(10);
    }

    #[Test]
    public function rewind(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->read(3);
        $this->assertEquals(3, $partial->tell());

        $partial->rewind();
        $this->assertEquals(0, $partial->tell());
        $this->assertEquals('World', $partial->read(5));
    }

    #[Test]
    public function getContents(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $this->assertEquals('World', $partial->getContents());
    }

    #[Test]
    public function getContentsFromMiddle(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->read(2);
        $this->assertEquals('rld', $partial->getContents());
    }

    #[Test]
    public function toStringRewindsAndReadsAll(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->read(2);
        $this->assertEquals('World', (string) $partial);
    }

    #[Test]
    public function streamIsReadable(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertTrue($partial->isReadable());
    }

    #[Test]
    public function streamIsNotWritable(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->assertFalse($partial->isWritable());
    }

    #[Test]
    public function writeThrows(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to a partial stream');
        $partial->write('test');
    }

    #[Test]
    public function getMetadataWithoutKey(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $metadata = $partial->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('partial_stream', $metadata);
        $this->assertEquals(6, $metadata['partial_stream']['start']);
        $this->assertEquals(5, $metadata['partial_stream']['length']);
        $this->assertEquals(0, $metadata['partial_stream']['position']);
    }

    #[Test]
    public function getMetadataWithKey(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $uri = $partial->getMetadata('uri');
        $this->assertEquals('php://memory', $uri);
    }

    #[Test]
    public function close(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 5);

        $partial->close();
        $this->assertFalse($partial->isReadable());
    }

    #[Test]
    public function detach(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 6, 5);

        $partial->read(2);
        $resource = $partial->detach();

        $this->assertIsResource($resource);
        $this->assertEquals(0, $partial->tell());
        $this->assertNull($partial->getSize());
    }

    #[Test]
    public function zeroLengthStream(): void
    {
        $stream = $this->createTestStream('Hello World');
        $partial = new PartialStream($stream, 0, 0);

        $this->assertEquals(0, $partial->getSize());
        $this->assertEquals('', $partial->read(10));
        $this->assertTrue($partial->eof());
    }

    #[Test]
    public function largeStreamChunkedReading(): void
    {
        $content = str_repeat('A', 10000);
        $stream = $this->createTestStream($content);
        $partial = new PartialStream($stream, 100, 5000);

        $readContent = '';
        while (!$partial->eof()) {
            $readContent .= $partial->read(1024);
        }

        $this->assertEquals(5000, strlen($readContent));
        $this->assertEquals(str_repeat('A', 5000), $readContent);
    }

    #[Test]
    public function multipleReadsAcrossBoundary(): void
    {
        $stream = $this->createTestStream('0123456789');
        $partial = new PartialStream($stream, 2, 5);

        $this->assertEquals('23', $partial->read(2));
        $this->assertEquals('456', $partial->read(5));
        $this->assertEquals('', $partial->read(5));
    }

    #[Test]
    public function seekAndReadPattern(): void
    {
        $stream = $this->createTestStream('ABCDEFGHIJ');
        $partial = new PartialStream($stream, 2, 6);

        $partial->seek(1);
        $this->assertEquals('D', $partial->read(1));

        $partial->seek(3);
        $this->assertEquals('FG', $partial->read(2));

        $partial->rewind();
        $this->assertEquals('CDEFGH', $partial->read(10));
    }

    #[Test]
    public function endOfStreamWithinPartialRange(): void
    {
        // Create a stream with less content than requested range
        $stream = $this->createTestStream('Hello');
        $partial = new PartialStream($stream, 0, 10); // Request more than available

        $content = $partial->read(10);
        $this->assertEquals('Hello', $content);
        $this->assertEquals(5, strlen($content));
    }
}
