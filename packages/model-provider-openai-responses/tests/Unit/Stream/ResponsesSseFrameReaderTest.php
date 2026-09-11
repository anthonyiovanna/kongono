<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Stream;

use Kongono\Http\Message\Message\Response;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrame;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrameReader;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class ResponsesSseFrameReaderTest extends TestCase
{
    public function testLfAndCrLfFramesUseOnlyBoundedReadsAndCloseOnce(): void
    {
        $stream = new SseTestStream("event: response.created\r\ndata: {\"type\":\"response.created\",\"sequence_number\":0}\r\n\r\n");
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'Text/Event-Stream; charset="UTF-8"'], $stream));
        $frame = $reader->next();
        self::assertNotNull($frame);
        self::assertSame('response.created', $frame->event());
        self::assertStringStartsWith('{', $frame->data());
        self::assertNull($reader->next());
        $reader->close();
        $reader->close();
        self::assertSame(1, $stream->closeCalls);
        self::assertGreaterThan(0, $stream->readCalls);
        self::assertSame(0, $stream->forbiddenCalls);
    }

    public function testForbiddenGrammarAndImmediateStallFailClosed(): void
    {
        foreach ([
            ": keepalive\n\n",
            "data: [DONE]\n\n",
            "id: x\ndata: {}\n\n",
            "retry: 1\ndata: {}\n\n",
            "unknown: x\ndata: {}\n\n",
            "event: x\nevent: x\ndata: {}\n\n",
            "data: {}\ndata: {}\n\n",
            "data: first\n second\n\n",
            "data:\n\n",
            "\xEF\xBB\xBFdata: {}\n\n",
            "data: \xFF\n\n",
            "data: {}\n",
            "data: {}\r\r",
        ] as $bytes) {
            $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], new SseTestStream($bytes)));
            try {
                $reader->next();
                self::fail('Forbidden framing must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1715', $failure->diagnosticId());
            } finally {
                $reader->close();
            }
        }
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], new SseTestStream('data: {}\n\n', stall: true)));
        $this->expectException(OpenAiResponsesInvocationException::class);
        $reader->next();
    }

    public function testOneFrameSurvivesEveryPossibleSingleByteSplit(): void
    {
        $bytes = "event: response.created\ndata: {\"sequence_number\":0,\"type\":\"response.created\"}\n\n";
        for ($split = 1; $split < strlen($bytes); ++$split) {
            $stream = new SseTestStream($bytes, chunkPlan: [$split, strlen($bytes) - $split]);
            $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
            $frame = $reader->next();
            self::assertNotNull($frame);
            self::assertSame('response.created', $frame->event());
            self::assertSame('{"sequence_number":0,"type":"response.created"}', $frame->data());
            self::assertNull($reader->next());
            $reader->close();
            self::assertSame(1, $stream->closeCalls);
            self::assertSame(0, $stream->forbiddenCalls);
        }
    }

    public function testOnePullStopsAtFirstUsableFrame(): void
    {
        $first = "data: {\"sequence_number\":0," . '"type":"response.created"}' . "\n\n";
        $second = "data: {\"sequence_number\":1," . '"type":"response.in_progress"}' . "\n\n";
        $stream = new SseTestStream($first . $second, chunkPlan: [strlen($first), strlen($second)]);
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
        $firstFrame = $reader->next();
        self::assertNotNull($firstFrame);
        self::assertStringContainsString('response.created', $firstFrame->data());
        self::assertSame(1, $stream->readCalls);
        self::assertNotNull($reader->next());
        self::assertSame(2, $stream->readCalls);
        $reader->close();
    }

    public function testMultipleFramesFromOneChunkRemainIndividuallyPullable(): void
    {
        $bytes = "data: first\n\ndata: second\n\n";
        $stream = new SseTestStream($bytes, chunkBytes: strlen($bytes));
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
        $first = $reader->next();
        self::assertNotNull($first);
        self::assertSame('first', $first->data());
        self::assertSame(1, $stream->readCalls);
        self::assertSame('second', $reader->next()?->data());
        self::assertSame(1, $stream->readCalls);
        self::assertNull($reader->next());
        $reader->close();
    }

    public function testContentTypeReadabilityAndKnownBodyBoundsFailClosed(): void
    {
        foreach ([
            new Response(201, ['Content-Type' => 'text/event-stream'], new SseTestStream('')),
            new Response(200, [], new SseTestStream('')),
            new Response(200, ['Content-Type' => ['text/event-stream', 'text/event-stream']], new SseTestStream('')),
            new Response(200, ['Content-Type' => 'text/event-stream; boundary=x'], new SseTestStream('')),
            new Response(200, ['Content-Type' => 'text/event-stream'], new SseTestStream('', readable: false)),
        ] as $response) {
            try {
                new ResponsesSseFrameReader($response);
                self::fail('Invalid HTTP stream evidence must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertSame('KGN-OPENAI-RESPONSES-1715', $failure->diagnosticId());
            }
        }

        foreach ([ResponsesSseFrameReader::MAXIMUM_TOTAL_BYTES - 1, ResponsesSseFrameReader::MAXIMUM_TOTAL_BYTES] as $size) {
            $stream = new SseTestStream('', reportedSize: $size);
            $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
            $reader->close();
            self::assertSame(1, $stream->closeCalls);
        }
        $stream = new SseTestStream('', reportedSize: ResponsesSseFrameReader::MAXIMUM_TOTAL_BYTES + 1);
        try {
            new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
            self::fail('Known oversized stream evidence must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1715', $failure->diagnosticId());
            self::assertSame(1, $stream->closeCalls);
        }
    }

    public function testLineDataAndUnknownBodyBoundaryTripletsAreEnforced(): void
    {
        foreach ([ResponsesSseFrameReader::MAXIMUM_DATA_BYTES - 1, ResponsesSseFrameReader::MAXIMUM_DATA_BYTES] as $size) {
            self::assertSame($size, strlen((new ResponsesSseFrame(null, str_repeat('x', $size)))->data()));
        }
        $this->assertStreamingFailure(static fn(): ResponsesSseFrame => new ResponsesSseFrame(null, str_repeat('x', ResponsesSseFrameReader::MAXIMUM_DATA_BYTES + 1)));

        foreach ([ResponsesSseFrameReader::MAXIMUM_LINE_BYTES - 1, ResponsesSseFrameReader::MAXIMUM_LINE_BYTES] as $lineBytes) {
            $eventValue = str_repeat('x', $lineBytes - strlen('event: '));
            $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], new SseTestStream("event: {$eventValue}\ndata: {}\n\n", chunkBytes: 8192, unknownSize: true)));
            self::assertNotNull($reader->next());
            $reader->close();
        }
        $oversized = 'event: ' . str_repeat('x', ResponsesSseFrameReader::MAXIMUM_LINE_BYTES + 1 - strlen('event: ')) . "\ndata: {}\n\n";
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], new SseTestStream($oversized, chunkBytes: 8192, unknownSize: true)));
        try {
            $reader->next();
            self::fail('Oversized retained line evidence must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1715', $failure->diagnosticId());
        } finally {
            $reader->close();
        }

        $boundedFrame = 'data: ' . str_repeat('x', ResponsesSseFrameReader::MAXIMUM_LINE_BYTES - strlen('data: ')) . "\n\n";
        $tooLarge = str_repeat($boundedFrame, 9);
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], new SseTestStream($tooLarge, chunkBytes: 8192, unknownSize: true)));
        try {
            while (null !== $reader->next()) {
            }
            self::fail('Unknown oversized body evidence must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1715', $failure->diagnosticId());
        } finally {
            $reader->close();
        }
    }

    public function testReadAndCleanupFailuresAreBoundedAndComposable(): void
    {
        $readSentinel = 'private-read-sentinel-17d';
        $stream = new SseTestStream('x', readFailure: new RuntimeException($readSentinel), unknownSize: true);
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
        try {
            $reader->next();
            self::fail('Read failure must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertStringNotContainsString($readSentinel, (string) $failure);
        } finally {
            $reader->close();
        }

        $cleanupSentinel = 'private-cleanup-sentinel-17d';
        try {
            new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'application/json'], new SseTestStream('', closeFailure: new RuntimeException($cleanupSentinel))));
            self::fail('Validation and cleanup failure must compose.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1718', $failure->diagnosticId());
            self::assertInstanceOf(RuntimeException::class, $failure->cleanupFailure());
            self::assertStringNotContainsString($cleanupSentinel, (string) $failure);
        }
    }

    /** @param callable(): ResponsesSseFrame $operation */
    private function assertStreamingFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Invalid streaming evidence must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertContains($failure->diagnosticId(), ['KGN-OPENAI-RESPONSES-1715', 'KGN-OPENAI-RESPONSES-1718']);
        }
    }
}

final class SseTestStream implements StreamInterface
{
    public int $closeCalls = 0;
    public int $readCalls = 0;
    public int $forbiddenCalls = 0;
    private int $position = 0;

    /** @param list<int> $chunkPlan */
    public function __construct(
        private string $bytes,
        private bool $stall = false,
        private int $chunkBytes = 3,
        private array $chunkPlan = [],
        private bool $readable = true,
        private ?int $reportedSize = null,
        private bool $unknownSize = false,
        private ?RuntimeException $readFailure = null,
        private ?RuntimeException $closeFailure = null,
    ) {}
    public function __toString(): string
    {
        ++$this->forbiddenCalls;
        return '';
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
        ++$this->forbiddenCalls;
        return null;
    }
    public function getSize(): ?int
    {
        return $this->unknownSize ? null : ($this->reportedSize ?? strlen($this->bytes));
    }
    public function tell(): int
    {
        return $this->position;
    }
    public function eof(): bool
    {
        return $this->position >= strlen($this->bytes);
    }
    public function isSeekable(): bool
    {
        return false;
    }
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        ++$this->forbiddenCalls;
        throw new RuntimeException('seek');
    }
    public function rewind(): void
    {
        ++$this->forbiddenCalls;
        throw new RuntimeException('rewind');
    }
    public function isWritable(): bool
    {
        return false;
    }
    public function write(string $string): int
    {
        throw new RuntimeException('write');
    }
    public function isReadable(): bool
    {
        return $this->readable;
    }
    public function read(int $length): string
    {
        ++$this->readCalls;
        if ($this->stall) {
            return '';
        }
        if (null !== $this->readFailure) {
            throw $this->readFailure;
        }
        $planned = [] === $this->chunkPlan ? $this->chunkBytes : array_shift($this->chunkPlan);
        $chunk = substr($this->bytes, $this->position, min($length, $planned));
        $this->position += strlen($chunk);
        return $chunk;
    }
    public function getContents(): string
    {
        ++$this->forbiddenCalls;
        return '';
    }
    public function getMetadata(?string $key = null)
    {
        return null;
    }
}
