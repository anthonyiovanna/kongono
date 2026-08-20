<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Message;

use Kongono\Http\Message\Exception\ExceptionInterface;
use Kongono\Http\Message\Exception\HttpMessageRuntimeException;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Message\Stream;
use Kongono\Http\Message\Message\UploadedFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class UploadedFileTest extends TestCase
{
    public function testMetadataValidationAndErrorState(): void
    {
        $stream = $this->stream('content');
        $upload = new UploadedFile($stream, 7, UPLOAD_ERR_OK, 'name.txt', 'text/plain');
        self::assertSame(7, $upload->getSize());
        self::assertSame(UPLOAD_ERR_OK, $upload->getError());
        self::assertSame('name.txt', $upload->getClientFilename());
        self::assertSame('text/plain', $upload->getClientMediaType());
        self::assertSame($stream, $upload->getStream());
        foreach ([UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION] as $error) {
            self::assertSame($error, (new UploadedFile($stream, error: $error))->getError());
        }
        self::assertNull((new UploadedFile($stream))->getSize());
        self::assertSame(0, (new UploadedFile($stream, 0))->getSize());
        foreach ([-1, 5, 9] as $error) {
            try {
                new UploadedFile($stream, error: $error);
                self::fail('Bad error must fail.');
            } catch (InvalidMessageArgumentException) {
                self::addToAssertionCount(1);
            }
        }
        try {
            new UploadedFile($stream, -1);
            self::fail('Bad size must fail.');
        } catch (InvalidMessageArgumentException) {
            self::addToAssertionCount(1);
        }
        try {
            (new UploadedFile($stream, error: UPLOAD_ERR_NO_FILE))->getStream();
            self::fail('Error upload must fail.');
        } catch (HttpMessageRuntimeException) {
            self::addToAssertionCount(1);
        }
        $errorUpload = new UploadedFile($stream, error: UPLOAD_ERR_NO_FILE);
        try {
            $errorUpload->moveTo('unused-target');
            self::fail('An upload error must prohibit movement.');
        } catch (HttpMessageRuntimeException $exception) {
            self::assertSame('KGN-HTTP-1023', $exception->diagnosticId());
            self::assertSame('upload_error', $exception->diagnosticContext()->fields()[0]->value());
        }
        $stream->close();
    }

    public function testMoveReplacesTargetAndDoesNotCloseSource(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kgn-http-');
        self::assertIsString($path);
        file_put_contents($path, 'old');
        $content = str_repeat('x', 8193);
        $source = $this->stream($content);
        $stream = new class ($source) implements StreamInterface {
            /** @var list<int> */
            public array $requestedReadLengths = [];
            public function __construct(private readonly StreamInterface $source) {}
            public function __toString(): string
            {
                return (string) $this->source;
            }
            public function close(): void
            {
                $this->source->close();
            }
            public function detach()
            {
                return $this->source->detach();
            }
            public function getSize(): ?int
            {
                return $this->source->getSize();
            }
            public function tell(): int
            {
                return $this->source->tell();
            }
            public function eof(): bool
            {
                return $this->source->eof();
            }
            public function isSeekable(): bool
            {
                return $this->source->isSeekable();
            }
            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                $this->source->seek($offset, $whence);
            }
            public function rewind(): void
            {
                $this->source->rewind();
            }
            public function isWritable(): bool
            {
                return $this->source->isWritable();
            }
            public function write(string $string): int
            {
                return $this->source->write($string);
            }
            public function isReadable(): bool
            {
                return $this->source->isReadable();
            }
            public function read(int $length): string
            {
                $this->requestedReadLengths[] = $length;

                return $this->source->read($length);
            }
            public function getContents(): string
            {
                return $this->source->getContents();
            }
            public function getMetadata(?string $key = null)
            {
                return $this->source->getMetadata($key);
            }
        };
        $upload = new UploadedFile($stream);
        try {
            self::assertSame($stream, $upload->getStream());
            $upload->moveTo($path);
            self::assertSame($content, file_get_contents($path));
            self::assertGreaterThanOrEqual(2, count($stream->requestedReadLengths));
            foreach ($stream->requestedReadLengths as $requestedLength) {
                self::assertGreaterThanOrEqual(1, $requestedLength);
                self::assertLessThanOrEqual(8192, $requestedLength);
            }
            self::assertTrue($source->isReadable());
            try {
                $upload->getStream();
                self::fail('Moved stream must fail.');
            } catch (HttpMessageRuntimeException $exception) {
                self::assertSame('KGN-HTTP-1023', $exception->diagnosticId());
                self::assertSame('moved', $exception->diagnosticContext()->fields()[0]->value());
            }
            try {
                $upload->moveTo($path);
                self::fail('Second move must fail.');
            } catch (HttpMessageRuntimeException $e) {
                self::assertSame('KGN-HTTP-1025', $e->diagnosticId());
            }
        } finally {
            $source->close();
            @unlink($path);
        }
    }

    public function testInvalidTargetsDoNotDisclosePath(): void
    {
        $stream = $this->stream('x');
        $upload = new UploadedFile($stream);
        foreach (['', "bad\0path"] as $path) {
            try {
                $upload->moveTo($path);
                self::fail('Bad target must fail.');
            } catch (InvalidMessageArgumentException $e) {
                if ('' !== $path) {
                    self::assertStringNotContainsString($path, $e->getMessage());
                } else {
                    self::assertSame('Invalid upload target', $e->getMessage());
                }
            }
        }
        $stream->close();
    }

    public function testSeekableMoveRewindsAndOpenFailureIsTyped(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'kgn-http-seekable-');
        self::assertIsString($target);
        $stream = $this->stream('complete');
        $stream->seek(4);
        try {
            (new UploadedFile($stream))->moveTo($target);
            self::assertSame('complete', file_get_contents($target));
        } finally {
            $stream->close();
            @unlink($target);
        }

        $source = $this->stream('content');
        $missingParent = tempnam(sys_get_temp_dir(), 'kgn-http-missing-parent-');
        self::assertIsString($missingParent);
        @unlink($missingParent);
        try {
            (new UploadedFile($source))->moveTo($missingParent . DIRECTORY_SEPARATOR . 'target');
            self::fail('Target-open failure must be typed.');
        } catch (HttpMessageRuntimeException $exception) {
            self::assertSame('KGN-HTTP-1026', $exception->diagnosticId());
            self::assertSame('open_target', $exception->diagnosticContext()->fields()[0]->value());
            $this->assertRawAbsentFromPackageSurfaces($missingParent, $exception);
        } finally {
            $source->close();
        }
    }

    public function testNonSeekableMoveStartsAtCurrentCursor(): void
    {
        $pair = stream_socket_pair(STREAM_PF_INET, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($pair);
        [$socket, $peer] = $pair;
        fwrite($peer, 'prefix-content');
        stream_socket_shutdown($peer, STREAM_SHUT_WR);
        $stream = new Stream($socket);
        self::assertSame('prefix-', $stream->read(7));
        $target = tempnam(sys_get_temp_dir(), 'kgn-http-nonseekable-');
        self::assertIsString($target);
        try {
            (new UploadedFile($stream))->moveTo($target);
            self::assertSame('content', file_get_contents($target));
        } finally {
            $stream->close();
            fclose($peer);
            @unlink($target);
        }
    }

    public function testTargetWriteFailureUsesTheBoundedRuntimeDiagnostic(): void
    {
        $protocol = 'kgnhttpfailwrite';
        self::assertNotContains($protocol, stream_get_wrappers());
        $wrapper = new class {
            public mixed $context;
            public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
            {
                return true;
            }
            public function stream_write(string $data): int
            {
                return 0;
            }
            public function stream_close(): void {}
        };
        self::assertTrue(stream_wrapper_register($protocol, $wrapper::class));
        $stream = $this->stream('content');
        try {
            try {
                (new UploadedFile($stream))->moveTo($protocol . '://target');
                self::fail('Target-write failure must be typed.');
            } catch (HttpMessageRuntimeException $exception) {
                self::assertSame('KGN-HTTP-1026', $exception->diagnosticId());
                self::assertSame('copy_target', $exception->diagnosticContext()->fields()[0]->value());
                $this->assertRawAbsentFromPackageSurfaces($protocol, $exception);
            }
        } finally {
            $stream->close();
            self::assertTrue(stream_wrapper_unregister($protocol));
        }
    }

    public function testSuppliedStreamFailurePropagatesUnchanged(): void
    {
        $sentinel = new \RuntimeException('sentinel');
        $stream = new class ($sentinel) implements StreamInterface {
            public function __construct(private readonly \Throwable $sentinel) {}
            public function __toString(): string
            {
                return '';
            }
            public function close(): void {}
            public function detach()
            {
                return null;
            }
            public function getSize(): ?int
            {
                return null;
            }
            public function tell(): int
            {
                return 0;
            }
            public function eof(): bool
            {
                return false;
            }
            public function isSeekable(): bool
            {
                return false;
            }
            public function seek(int $offset, int $whence = SEEK_SET): void {}
            public function rewind(): void {}
            public function isWritable(): bool
            {
                return false;
            }
            public function write(string $string): int
            {
                return 0;
            }
            public function isReadable(): bool
            {
                return true;
            }
            public function read(int $length): string
            {
                throw $this->sentinel;
            }
            public function getContents(): string
            {
                throw $this->sentinel;
            }
            public function getMetadata(?string $key = null)
            {
                return null;
            }
        };
        $target = tempnam(sys_get_temp_dir(), 'kgn-http-sentinel-');
        self::assertIsString($target);
        try {
            try {
                (new UploadedFile($stream))->moveTo($target);
                self::fail('The supplied stream failure must propagate.');
            } catch (\Throwable $exception) {
                self::assertSame($sentinel, $exception);
            }
        } finally {
            @unlink($target);
        }
    }

    private function stream(string $content): Stream
    {
        $resource = fopen('php://temp', 'w+b');
        self::assertIsResource($resource);
        fwrite($resource, $content);
        rewind($resource);
        return new Stream($resource);
    }

    private function assertRawAbsentFromPackageSurfaces(string $raw, ExceptionInterface $exception): void
    {
        $surfaces = [
            $exception->getMessage(),
            $exception->diagnosticId(),
            $exception->diagnosticTitle(),
            $exception->diagnosticRemediation(),
            $exception->diagnosticContext()->operation(),
            $exception->diagnosticContext()->violatedInvariant(),
            ...array_map(
                static fn($field): string => $field->key() . '|' . $field->label() . '|' . (string) $field->value(),
                $exception->diagnosticContext()->fields(),
            ),
        ];
        foreach ($surfaces as $surface) {
            self::assertStringNotContainsString($raw, $surface);
        }
    }
}
