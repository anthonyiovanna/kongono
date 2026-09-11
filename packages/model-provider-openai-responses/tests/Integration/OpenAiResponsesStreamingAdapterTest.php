<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Integration;

use Kongono\Http\Message\Message\Response;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelInvocationRuntime\Runtime\ModelInvocationRuntime;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class OpenAiResponsesStreamingAdapterTest extends TestCase
{
    public function testNeutralRuntimeReceivesContiguousPullBasedEventsAndFinalResult(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $runtimeRequest = new InvocationRuntimeRequest(
            $fixture->source,
            $fixture->artifactBytes,
            new ReceiptIdentity('receipt.model.openai.responses.stream'),
            new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context),
            new class implements InvocationCancellation {
                public function isCancellationRequested(): bool
                {
                    return false;
                }
            },
            new class implements InvocationMonotonicClock {
                private int $value = 0;
                public function milliseconds(): int
                {
                    return $this->value++;
                }
            },
        );

        $result = (new ModelInvocationRuntime())->run($runtimeRequest);
        self::assertSame('succeeded', $result->receipt()->outcome()->value);
        self::assertSame('synthetic-stream-output', $result->result()?->text()?->value());
        self::assertSame(
            [StreamEventKind::Started, StreamEventKind::ContentDelta, StreamEventKind::ContentDelta, StreamEventKind::Usage, StreamEventKind::Completed],
            array_map(static fn($event): StreamEventKind => $event->kind(), $result->events()),
        );
        self::assertSame([1, 2, 3, 4, 5], array_map(static fn($event): int => $event->sequence()->value(), $result->events()));
        $eventUsage = $result->events()[3]->usage();
        $resultUsage = $result->result()->usage();
        self::assertNotNull($eventUsage);
        self::assertNotNull($resultUsage);
        self::assertTrue($eventUsage->equals($resultUsage));
        self::assertSame(1, $fixture->createCalls);
        self::assertFalse($fixture->responseBodyReadable());
    }

    public function testIncompleteAndFailedTerminalsNormalizeThroughNeutralRuntime(): void
    {
        foreach ([
            ['max_tokens', FinishReason::Length],
            ['content_filter', FinishReason::ContentFiltered],
        ] as [$reason, $expected]) {
            $body = str_replace('"max_tokens"', '"' . $reason . '"', OpenAiResponsesAdapterFixture::streamBody('incomplete-text-stream.sse'));
            $fixture = new OpenAiResponsesAdapterFixture(new Response(200, ['Content-Type' => 'text/event-stream'], new IntegrationSseStream($body)), streaming: true);
            $result = $this->executeFixture($fixture);
            self::assertSame('succeeded', $result->receipt()->outcome()->value);
            $modelResult = $result->result();
            self::assertNotNull($modelResult);
            self::assertSame($expected, $modelResult->finishReason());
            self::assertSame('partial', $modelResult->text()?->value());
            self::assertSame([StreamEventKind::Started, StreamEventKind::ContentDelta, StreamEventKind::Completed], array_map(static fn($event): StreamEventKind => $event->kind(), $result->events()));
        }

        $failedFixture = new OpenAiResponsesAdapterFixture(new Response(200, ['Content-Type' => 'text/event-stream'], new IntegrationSseStream(OpenAiResponsesAdapterFixture::streamBody('failed-text-stream.sse'))), streaming: true);
        $failed = $this->executeFixture($failedFixture);
        self::assertSame('failed', $failed->receipt()->outcome()->value);
        self::assertNull($failed->result());
        self::assertSame([StreamEventKind::Started, StreamEventKind::Failed], array_map(static fn($event): StreamEventKind => $event->kind(), $failed->events()));
        self::assertSame(1, $failedFixture->createCalls);
    }

    public function testCancellationBeforeAndBetweenPullsStopsEffectsAndClosesOnce(): void
    {
        $before = new OpenAiResponsesAdapterFixture(streaming: true);
        $cancelled = $this->executeFixture($before, new IntegrationInvocationCancellation([true]));
        self::assertSame('cancelled', $cancelled->receipt()->outcome()->value);
        self::assertSame(0, $before->createCalls);

        $body = OpenAiResponsesAdapterFixture::streamBody('completed-text-stream.sse');
        $firstFrameOffset = strpos($body, "\n\n");
        self::assertNotFalse($firstFrameOffset);
        $firstFrameBytes = $firstFrameOffset + 2;
        $stream = new IntegrationSseStream($body, [$firstFrameBytes, strlen($body) - $firstFrameBytes]);
        $between = new OpenAiResponsesAdapterFixture(new Response(200, ['Content-Type' => 'text/event-stream'], $stream), streaming: true);
        $cancelled = $this->executeFixture($between, new IntegrationInvocationCancellation([false, false, false, true]));
        self::assertSame('cancelled', $cancelled->receipt()->outcome()->value);
        self::assertSame([StreamEventKind::Started], array_map(static fn($event): StreamEventKind => $event->kind(), $cancelled->events()));
        self::assertSame(1, $stream->readCalls);
        self::assertSame(1, $stream->closeCalls);
        self::assertSame(1, $between->createCalls);
    }

    public function testReadAndCleanupFailureIsNondisclosingAndNextOperationIsHealthy(): void
    {
        $readSentinel = 'private-read-integration-17d';
        $closeSentinel = 'private-close-integration-17d';
        $stream = new IntegrationSseStream('x', readFailure: new RuntimeException($readSentinel), closeFailure: new RuntimeException($closeSentinel));
        $broken = new OpenAiResponsesAdapterFixture(new Response(200, ['Content-Type' => 'text/event-stream'], $stream), streaming: true);
        $runtime = new ModelInvocationRuntime();
        try {
            $runtime->run($this->request($broken, new IntegrationInvocationCancellation([]), 'broken'));
            self::fail('Read and cleanup failure must fail.');
        } catch (\Throwable $failure) {
            self::assertStringNotContainsString($readSentinel, (string) $failure);
            self::assertStringNotContainsString($closeSentinel, (string) $failure);
            self::assertSame(1, $stream->closeCalls);
            self::assertSame(1, $broken->createCalls);
        }

        $healthy = new OpenAiResponsesAdapterFixture(streaming: true);
        $result = $runtime->run($this->request($healthy, new IntegrationInvocationCancellation([]), 'healthy'));
        self::assertSame('succeeded', $result->receipt()->outcome()->value);
        self::assertSame(1, $healthy->createCalls);
    }

    public function testUncertainExternalOutcomeIsNeverReplayedAndRuntimeRemainsReusable(): void
    {
        $runtime = new ModelInvocationRuntime();
        foreach (['failure_before_effect', 'uncertain_effect'] as $mode) {
            $fixture = new OpenAiResponsesAdapterFixture(transportMode: $mode, streaming: true);
            try {
                $runtime->run($this->request($fixture, new IntegrationInvocationCancellation([]), $mode));
                self::fail('Terminal external outcome must fail.');
            } catch (\Throwable) {
                self::assertSame(1, $fixture->createCalls);
                self::assertSame(1, $fixture->transport?->openCalls);
                $transportSession = $fixture->transportSession;
                self::assertNotNull($transportSession);
                self::assertLessThanOrEqual(2, $transportSession->nextCalls);
            }

            $healthy = new OpenAiResponsesAdapterFixture(streaming: true);
            self::assertSame('succeeded', $runtime->run($this->request($healthy, new IntegrationInvocationCancellation([]), 'healthy-' . $mode))->receipt()->outcome()->value);
        }
    }

    private function executeFixture(OpenAiResponsesAdapterFixture $fixture, ?InvocationCancellation $cancellation = null): InvocationRuntimeResult
    {
        return (new ModelInvocationRuntime())->run($this->request($fixture, $cancellation ?? new IntegrationInvocationCancellation([]), 'run'));
    }

    private function request(OpenAiResponsesAdapterFixture $fixture, InvocationCancellation $cancellation, string $suffix): InvocationRuntimeRequest
    {
        return new InvocationRuntimeRequest(
            $fixture->source,
            $fixture->artifactBytes,
            new ReceiptIdentity('receipt.model.openai.responses.' . str_replace('_', '-', $suffix)),
            new OpenAiResponsesInvocationAdapter($fixture->openAiProfile, $fixture->context),
            $cancellation,
            new class implements InvocationMonotonicClock {
                private int $value = 0;
                public function milliseconds(): int
                {
                    return $this->value++;
                }
            },
        );
    }
}

final class IntegrationInvocationCancellation implements InvocationCancellation
{
    /** @param list<bool> $answers */
    public function __construct(private array $answers) {}

    public function isCancellationRequested(): bool
    {
        return array_shift($this->answers) ?? false;
    }
}

final class IntegrationSseStream implements StreamInterface
{
    public int $readCalls = 0;
    public int $closeCalls = 0;
    private int $position = 0;

    /** @param list<int> $chunks */
    public function __construct(
        private string $bytes,
        private array $chunks = [],
        private ?RuntimeException $readFailure = null,
        private ?RuntimeException $closeFailure = null,
    ) {}

    public function __toString(): string
    {
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
        return null;
    }
    public function getSize(): ?int
    {
        return null;
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
        throw new RuntimeException('seek');
    }
    public function rewind(): void
    {
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
        return true;
    }
    public function read(int $length): string
    {
        ++$this->readCalls;
        if (null !== $this->readFailure) {
            throw $this->readFailure;
        }
        $chunkBytes = [] === $this->chunks ? $length : array_shift($this->chunks);
        $chunk = substr($this->bytes, $this->position, min($length, $chunkBytes));
        $this->position += strlen($chunk);

        return $chunk;
    }
    public function getContents(): string
    {
        throw new RuntimeException('getContents');
    }
    public function getMetadata(?string $key = null)
    {
        return null;
    }
}
