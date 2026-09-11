<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Internal;

use Kongono\Http\Message\Message\Response;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesResponseBodyReader;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class OpenAiResponsesResponseBodyReaderTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function acceptedContentTypes(): iterable
    {
        yield 'bare' => ['application/json'];
        yield 'case' => ['Application/JSON'];
        yield 'charset' => ['application/json; charset=UTF-8'];
        yield 'quoted charset' => ['application/json ; charset = "utf-8"'];
    }

    #[DataProvider('acceptedContentTypes')]
    public function testBoundedChunkReadsAndExactlyOneClose(string $contentType): void
    {
        $stream = new AdapterTestStream('bounded-body');
        $body = (new OpenAiResponsesResponseBodyReader())->read(new Response(200, ['Content-Type' => $contentType], $stream));
        self::assertSame('bounded-body', $body);
        self::assertSame(1, $stream->closeCalls);
        self::assertGreaterThan(0, $stream->readCalls);
        self::assertSame(0, $stream->stringCalls);
        self::assertSame(0, $stream->contentsCalls);
        self::assertSame(0, $stream->rewindCalls);
    }

    public function testHttpRejectionsCloseWithoutReading(): void
    {
        foreach ([
            new Response(500, ['Content-Type' => 'application/json'], $s1 = new AdapterTestStream('private-error-body')),
            new Response(200, [], $s2 = new AdapterTestStream('body')),
            new Response(200, ['Content-Type' => ['application/json', 'application/json']], $s3 = new AdapterTestStream('body')),
            new Response(200, ['Content-Type' => 'application/problem+json'], $s4 = new AdapterTestStream('body')),
        ] as $response) {
            try {
                (new OpenAiResponsesResponseBodyReader())->read($response);
                self::fail('Rejected HTTP response must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1712', $failure->diagnosticId());
                self::assertStringNotContainsString('private-error-body', (string) $failure);
            }
        }
        foreach ([$s1, $s2, $s3, $s4] as $stream) {
            self::assertSame(1, $stream->closeCalls);
            self::assertSame(0, $stream->readCalls);
        }
    }

    public function testOversizeLyingSizeStallAndReadFailureFailClosed(): void
    {
        $cases = [
            new AdapterTestStream('short', reportedSize: ResponsesResponseDecoder::MAXIMUM_BODY_BYTES + 1),
            new AdapterTestStream(str_repeat('x', ResponsesResponseDecoder::MAXIMUM_BODY_BYTES + 1), reportedSize: 1),
            new AdapterTestStream('stalled', stall: true),
            new AdapterTestStream('read-failure', readFailure: new RuntimeException('native-private-read')),
            new AdapterTestStream('unreadable', readable: false),
        ];
        foreach ($cases as $stream) {
            try {
                (new OpenAiResponsesResponseBodyReader())->read(new Response(200, ['Content-Type' => 'application/json'], $stream));
                self::fail('Invalid body stream must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-OPENAI-RESPONSES-1713']);
                self::assertStringNotContainsString('native-private-read', (string) $failure);
                self::assertSame(1, $stream->closeCalls);
            }
        }
    }

    public function testCleanupAndCombinedFailureKeepBothProgrammaticCausesNondisclosing(): void
    {
        $cleanup = new RuntimeException('native-private-cleanup');
        $stream = new AdapterTestStream('body', closeFailure: $cleanup);
        try {
            (new OpenAiResponsesResponseBodyReader())->read(new Response(200, ['Content-Type' => 'application/json'], $stream));
            self::fail('Cleanup failure must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame($cleanup, $failure->cleanupFailure());
            self::assertNull($failure->getPrevious());
            self::assertStringNotContainsString('native-private-cleanup', (string) $failure);
        }

        $read = new RuntimeException('native-private-read');
        $cleanup = new RuntimeException('native-private-cleanup');
        $stream = new AdapterTestStream('body', readFailure: $read, closeFailure: $cleanup);
        try {
            (new OpenAiResponsesResponseBodyReader())->read(new Response(200, ['Content-Type' => 'application/json'], $stream));
            self::fail('Combined failure must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            $primary = $failure->getPrevious();
            self::assertInstanceOf(OpenAiResponsesInvocationException::class, $primary);
            self::assertSame($read, $primary->getPrevious());
            self::assertSame($cleanup, $failure->cleanupFailure());
            self::assertSame(1, $stream->closeCalls);
            self::assertStringNotContainsString('native-private', (string) $failure);
        }
    }
}

final class AdapterTestStream implements StreamInterface
{
    public int $closeCalls = 0;
    public int $readCalls = 0;
    public int $stringCalls = 0;
    public int $contentsCalls = 0;
    public int $rewindCalls = 0;
    private int $position = 0;

    public function __construct(
        private string $data,
        private ?int $reportedSize = null,
        private bool $stall = false,
        private ?RuntimeException $readFailure = null,
        private ?RuntimeException $closeFailure = null,
        private bool $readable = true,
    ) {}

    public function __toString(): string
    {
        ++$this->stringCalls;
        return $this->data;
    }
    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }
    public function detach()
    {
        return null;
    }
    public function getSize(): int
    {
        return $this->reportedSize ?? strlen($this->data);
    }
    public function tell(): int
    {
        return $this->position;
    }
    public function eof(): bool
    {
        return $this->position >= strlen($this->data);
    }
    public function isSeekable(): bool
    {
        return false;
    }
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('not seekable');
    }
    public function rewind(): void
    {
        ++$this->rewindCalls;
        throw new RuntimeException('not seekable');
    }
    public function isWritable(): bool
    {
        return false;
    }
    public function write(string $string): int
    {
        throw new RuntimeException('not writable');
    }
    public function isReadable(): bool
    {
        return $this->readable;
    }
    public function read(int $length): string
    {
        ++$this->readCalls;
        if (null !== $this->readFailure) {
            throw $this->readFailure;
        }
        if ($this->stall) {
            return '';
        }
        $chunk = substr($this->data, $this->position, $length);
        $this->position += strlen($chunk);
        return $chunk;
    }
    public function getContents(): string
    {
        ++$this->contentsCalls;
        return substr($this->data, $this->position);
    }
    public function getMetadata(?string $key = null)
    {
        return null;
    }
}
