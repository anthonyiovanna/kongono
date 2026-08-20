<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Factory;

use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Factory\UploadedFileFactory;
use Kongono\Http\Message\Message\Stream;
use Kongono\Http\Message\Message\UploadedFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;

final class UploadedFileFactoryTest extends TestCase
{
    public function testFactoryIsStatelessAndUsesStreamSizeOnceWhenAbsent(): void
    {
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        fwrite($resource, 'abc');
        $stream = new Stream($resource);
        $factory = new UploadedFileFactory();
        $upload = $factory->createUploadedFile($stream, null, UPLOAD_ERR_OK, 'a', 'b');
        self::assertInstanceOf(UploadedFileFactoryInterface::class, $factory);
        self::assertInstanceOf(UploadedFile::class, $upload);
        self::assertSame(3, $upload->getSize());
        self::assertSame('a', $upload->getClientFilename());
        self::assertSame('b', $upload->getClientMediaType());
        self::assertSame([], (new \ReflectionClass($factory))->getProperties());
        $stream->close();
    }

    public function testFactoryReadsNullSizeExactlyOnceAndForwardsAllArguments(): void
    {
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        fwrite($resource, 'content');
        $inner = new Stream($resource);
        $stream = new class ($inner) implements StreamInterface {
            public int $sizeCalls = 0;

            public function __construct(private readonly StreamInterface $inner) {}
            public function __toString(): string
            {
                return (string) $this->inner;
            }
            public function close(): void
            {
                $this->inner->close();
            }
            public function detach()
            {
                return $this->inner->detach();
            }
            public function getSize(): ?int
            {
                ++$this->sizeCalls;
                return $this->inner->getSize();
            }
            public function tell(): int
            {
                return $this->inner->tell();
            }
            public function eof(): bool
            {
                return $this->inner->eof();
            }
            public function isSeekable(): bool
            {
                return $this->inner->isSeekable();
            }
            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                $this->inner->seek($offset, $whence);
            }
            public function rewind(): void
            {
                $this->inner->rewind();
            }
            public function isWritable(): bool
            {
                return $this->inner->isWritable();
            }
            public function write(string $string): int
            {
                return $this->inner->write($string);
            }
            public function isReadable(): bool
            {
                return $this->inner->isReadable();
            }
            public function read(int $length): string
            {
                return $this->inner->read($length);
            }
            public function getContents(): string
            {
                return $this->inner->getContents();
            }
            public function getMetadata(?string $key = null)
            {
                return $this->inner->getMetadata($key);
            }
        };
        $upload = (new UploadedFileFactory())->createUploadedFile($stream, null, UPLOAD_ERR_OK, 'client.txt', 'text/plain');
        self::assertSame(1, $stream->sizeCalls);
        self::assertSame(7, $upload->getSize());
        self::assertSame('client.txt', $upload->getClientFilename());
        self::assertSame('text/plain', $upload->getClientMediaType());
        self::assertSame($stream, $upload->getStream());
        $stream->close();
    }

    public function testFactoryRejectsUnreadableStreams(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kgn-http-upload-factory-');
        self::assertIsString($path);
        $resource = fopen($path, 'wb');
        self::assertIsResource($resource);
        $stream = new Stream($resource);
        try {
            $this->expectException(InvalidMessageArgumentException::class);
            (new UploadedFileFactory())->createUploadedFile($stream);
        } finally {
            $stream->close();
            @unlink($path);
        }
    }
}
