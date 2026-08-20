<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Message;

use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\Stream;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    public function testReadWriteSeekSizeMetadataAndDetach(): void
    {
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        $stream = new Stream($resource);
        self::assertTrue($stream->isReadable());
        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertSame(3, $stream->write('abc'));
        self::assertSame(3, $stream->tell());
        self::assertSame(3, $stream->getSize());
        $stream->rewind();
        self::assertSame('ab', $stream->read(2));
        self::assertSame('c', $stream->getContents());
        self::assertTrue($stream->eof());
        self::assertIsArray($stream->getMetadata());
        self::assertSame('w+b', $stream->getMetadata('mode'));
        self::assertNull($stream->getMetadata('missing-key'));
        $stream->seek(-1, SEEK_END);
        self::assertSame(2, $stream->tell());
        $stream->seek(-1, SEEK_CUR);
        self::assertSame(1, $stream->tell());
        $stream->seek(0, SEEK_SET);
        self::assertSame(0, $stream->tell());
        $detached = $stream->detach();
        self::assertSame($resource, $detached);
        self::assertTrue($stream->eof());
        self::assertSame([], $stream->getMetadata());
        self::assertNull($stream->getSize());
        fclose($resource);
    }

    public function testEveryPostDetachOperationUsesTheDetachedDiagnostic(): void
    {
        $resource = fopen('php://memory', 'r+b');
        self::assertIsResource($resource);
        $stream = new Stream($resource);
        self::assertSame($resource, $stream->detach());
        self::assertNull($stream->detach());
        $operations = [
            static fn() => $stream->tell(),
            static fn() => $stream->seek(0),
            static fn() => $stream->rewind(),
            static fn() => $stream->read(1),
            static fn() => $stream->write('x'),
            static fn() => $stream->getContents(),
        ];
        foreach ($operations as $operation) {
            try {
                $operation();
                self::fail('Detached stream operations must fail.');
            } catch (HttpMessageRuntimeException $exception) {
                self::assertSame('KGN-HTTP-1014', $exception->diagnosticId());
            }
        }
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertTrue($stream->eof());
        self::assertNull($stream->getSize());
        self::assertSame([], $stream->getMetadata());
        self::assertNull($stream->getMetadata('mode'));
        self::assertSame('', (string) $stream);
        fclose($resource);
    }

    public function testCapabilitiesAndFailuresUseCorrectCategories(): void
    {
        $read = fopen(__FILE__, 'rb');
        self::assertIsResource($read);
        $readStream = new Stream($read);
        self::assertTrue($readStream->isReadable());
        self::assertFalse($readStream->isWritable());
        try {
            $readStream->write('x');
            self::fail('Read-only write must fail.');
        } catch (HttpMessageRuntimeException $e) {
            self::assertSame('KGN-HTTP-1016', $e->diagnosticId());
        }
        $readStream->close();
        $readStream->close();
        try {
            $readStream->read(1);
            self::fail('Detached read must fail.');
        } catch (HttpMessageRuntimeException $e) {
            self::assertSame('KGN-HTTP-1014', $e->diagnosticId());
        }
        $negativeResource = fopen('php://memory', 'r+b');
        self::assertIsResource($negativeResource);
        $negativeStream = new Stream($negativeResource);
        self::assertSame('', (string) $readStream);
        try {
            $exception = $this->captureInvalidArgument(static fn() => $negativeStream->read(-1));
            self::assertSame('KGN-HTTP-1019', $exception->diagnosticId());
            self::assertSame([-1], array_map(
                static fn($field): string|int|bool => $field->value(),
                $exception->diagnosticContext()->fields(),
            ));
        } finally {
            $negativeStream->close();
        }
    }

    public function testInvalidResourceAndZeroReadBoundary(): void
    {
        $closed = fopen('php://memory', 'r+b');
        self::assertIsResource($closed);
        fclose($closed);
        try {
            new Stream($closed);
            self::fail('Invalid resource must fail.');
        } catch (InvalidMessageArgumentException) {
            self::addToAssertionCount(1);
        }
        $resource = fopen('php://memory', 'r+b');
        self::assertIsResource($resource);
        $stream = new Stream($resource);
        self::assertSame('', $stream->read(0));
        $stream->close();
    }

    public function testReadableWritableAndNonSeekableCapabilitiesAreExact(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kgn-http-stream-');
        self::assertIsString($path);
        file_put_contents($path, 'readable');
        $readResource = fopen($path, 'rb');
        self::assertIsResource($readResource);
        $read = new Stream($readResource);
        self::assertTrue($read->isReadable());
        self::assertFalse($read->isWritable());
        self::assertSame('read', $read->read(4));
        try {
            $read->write('x');
            self::fail('A read-only stream must reject writes.');
        } catch (HttpMessageRuntimeException $exception) {
            self::assertSame('KGN-HTTP-1016', $exception->diagnosticId());
        } finally {
            $read->close();
        }

        $writeResource = fopen($path, 'wb');
        self::assertIsResource($writeResource);
        $write = new Stream($writeResource);
        self::assertFalse($write->isReadable());
        self::assertTrue($write->isWritable());
        self::assertSame(3, $write->write('new'));
        self::assertSame('', (string) $write);
        try {
            $write->read(1);
            self::fail('A write-only stream must reject reads.');
        } catch (HttpMessageRuntimeException $exception) {
            self::assertSame('KGN-HTTP-1015', $exception->diagnosticId());
        } finally {
            $write->close();
            @unlink($path);
        }

        $pair = stream_socket_pair(STREAM_PF_INET, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$socket, $peer] = $pair;
        $nonSeekable = new Stream($socket);
        try {
            self::assertTrue($nonSeekable->isReadable());
            self::assertTrue($nonSeekable->isWritable());
            self::assertFalse($nonSeekable->isSeekable());
            self::assertSame('', (string) $nonSeekable);
            try {
                $nonSeekable->seek(0);
                self::fail('A non-seekable stream must reject seek.');
            } catch (HttpMessageRuntimeException $exception) {
                self::assertSame('KGN-HTTP-1017', $exception->diagnosticId());
            }
            fwrite($peer, 'socket');
            stream_socket_shutdown($peer, STREAM_SHUT_WR);
            self::assertSame('socket', $nonSeekable->read(6));
            self::assertTrue($nonSeekable->eof());
        } finally {
            $nonSeekable->close();
            fclose($peer);
        }
    }

    public function testNativeReadWriteAndSeekFailuresHaveExactOperationsAndToStringFailsClosed(): void
    {
        $this->withStreamWrapper('kgnread', new class {
            public mixed $context;
            public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
            {
                return true;
            }
            public function stream_read(int $count): false
            {
                return false;
            }
            public function stream_write(string $data): int
            {
                return strlen($data);
            }
            public function stream_seek(int $offset, int $whence = SEEK_SET): bool
            {
                return true;
            }
            public function stream_tell(): int
            {
                return 0;
            }
            public function stream_eof(): bool
            {
                return false;
            }
        }, function (string $uri): void {
            $resource = fopen($uri, 'r+b');
            self::assertIsResource($resource);
            $stream = new Stream($resource);
            $this->assertNativeOperationFailure(static fn() => $stream->read(1), 'read');
            self::assertSame('', (string) $stream);
            $stream->close();
        });

        $this->withStreamWrapper('kgnwrite', new class {
            public mixed $context;
            public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
            {
                return true;
            }
            public function stream_read(int $count): string
            {
                return '';
            }
            public function stream_write(string $data): false
            {
                return false;
            }
            public function stream_seek(int $offset, int $whence = SEEK_SET): bool
            {
                return true;
            }
            public function stream_tell(): int
            {
                return 0;
            }
            public function stream_eof(): bool
            {
                return true;
            }
        }, function (string $uri): void {
            $resource = fopen($uri, 'r+b');
            self::assertIsResource($resource);
            $stream = new Stream($resource);
            $this->assertNativeOperationFailure(static fn() => $stream->write('data'), 'write');
            $stream->close();
        });

        $this->withStreamWrapper('kgnseek', new class {
            public mixed $context;
            public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
            {
                return true;
            }
            public function stream_read(int $count): string
            {
                return '';
            }
            public function stream_write(string $data): int
            {
                return strlen($data);
            }
            public function stream_seek(int $offset, int $whence = SEEK_SET): bool
            {
                return false;
            }
            public function stream_tell(): int
            {
                return 0;
            }
            public function stream_eof(): bool
            {
                return true;
            }
        }, function (string $uri): void {
            $resource = fopen($uri, 'r+b');
            self::assertIsResource($resource);
            $stream = new Stream($resource);
            self::assertTrue($stream->isSeekable());
            $this->assertNativeOperationFailure(static fn() => $stream->seek(0), 'seek');
            $stream->close();
        });
    }

    /** @param callable(string): void $assertions */
    private function withStreamWrapper(string $prefix, object $wrapper, callable $assertions): void
    {
        $scheme = $prefix . bin2hex(random_bytes(6));
        self::assertNotContains($scheme, stream_get_wrappers());
        self::assertTrue(stream_wrapper_register($scheme, $wrapper::class));
        try {
            $assertions($scheme . '://proof');
        } finally {
            self::assertTrue(stream_wrapper_unregister($scheme));
        }
        self::assertNotContains($scheme, stream_get_wrappers());
    }

    /** @param callable(): mixed $operation */
    private function assertNativeOperationFailure(callable $operation, string $expectedOperation): void
    {
        try {
            $operation();
            self::fail('The native stream operation must fail.');
        } catch (HttpMessageRuntimeException $exception) {
            self::assertSame('KGN-HTTP-1018', $exception->diagnosticId());
            self::assertSame([$expectedOperation], array_map(
                static fn($field): string|int|bool => $field->value(),
                $exception->diagnosticContext()->fields(),
            ));
        }
    }

    /** @param callable(): mixed $operation */
    private function captureInvalidArgument(callable $operation): InvalidMessageArgumentException
    {
        try {
            $operation();
            self::fail('The invalid stream argument must fail.');
        } catch (InvalidMessageArgumentException $exception) {
            return $exception;
        }
    }
}
