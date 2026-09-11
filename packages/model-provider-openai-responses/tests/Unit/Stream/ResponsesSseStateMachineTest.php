<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Unit\Stream;

use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Response\ResponseIncompleteReason;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEvent;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventDecoder;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventKind;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrameReader;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseStateMachine;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;
use PHPUnit\Framework\TestCase;

final class ResponsesSseStateMachineTest extends TestCase
{
    public function testCompletedFixtureHasExactActionsAndTerminalSnapshot(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], (new StreamFactory())->createStream(OpenAiResponsesAdapterFixture::streamBody('completed-text-stream.sse'))));
        $machine = new ResponsesSseStateMachine($fixture->source->request(), $fixture->openAiProfile);
        $actions = [];
        while (null !== ($frame = $reader->next())) {
            $actions[] = $machine->accept((new ResponsesSseEventDecoder())->decode($frame));
        }
        self::assertSame(['started', 'none', 'none', 'none', 'delta', 'delta', 'none', 'none', 'none', 'terminal'], $actions);
        self::assertSame('synthetic-stream-output', $machine->terminalResponse()?->text()->value());
        $reader->close();
    }

    public function testSequenceGapFailsClosed(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $machine = new ResponsesSseStateMachine($fixture->source->request(), $fixture->openAiProfile);
        $event = (new ResponsesSseEventDecoder())->decode(new \Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrame(null, '{"response":{"id":"resp_x","model":"openai-test-model","object":"response","output":[],"status":"in_progress"},"sequence_number":1,"type":"response.created"}'));
        $this->expectException(OpenAiResponsesInvocationException::class);
        $machine->accept($event);
    }

    public function testIncompleteReasonsAndProviderFailureAreTerminal(): void
    {
        [$maximumMachine, $maximumActions] = $this->consume(OpenAiResponsesAdapterFixture::streamBody('incomplete-text-stream.sse'));
        self::assertSame('terminal', end($maximumActions));
        self::assertSame(ResponseIncompleteReason::MaxTokens, $maximumMachine->terminalResponse()?->incompleteReason());

        $filteredBody = str_replace('"max_tokens"', '"content_filter"', OpenAiResponsesAdapterFixture::streamBody('incomplete-text-stream.sse'));
        [$filteredMachine] = $this->consume($filteredBody);
        self::assertSame(ResponseIncompleteReason::ContentFilter, $filteredMachine->terminalResponse()?->incompleteReason());

        [$failedMachine, $failedActions] = $this->consume(OpenAiResponsesAdapterFixture::streamBody('failed-text-stream.sse'));
        self::assertSame('failed', end($failedActions));
        self::assertTrue($failedMachine->isTerminal());
        self::assertNull($failedMachine->terminalResponse());
        self::assertNotContains('delta', $failedActions);
    }

    public function testCorrelationSnapshotsUnsupportedSurfacesAndProviderDetailsFailClosed(): void
    {
        $body = OpenAiResponsesAdapterFixture::streamBody('completed-text-stream.sse');
        foreach ([
            str_replace('"item_id":"msg_stream_completed"', '"item_id":"msg_other"', $body),
            str_replace('"output_index":0', '"output_index":1', $body),
            str_replace('"annotations":[]', '"annotations":[{"private":"annotation-sentinel"}]', $body),
            str_replace('"text":"synthetic-stream-output"', '"text":"conflicting-text"', $body),
            str_replace('"status":"in_progress"', '"status":"queued"', $body),
            str_replace('"type":"response.output_text.delta"', '"type":"response.refusal.delta"', $body),
        ] as $invalid) {
            try {
                $this->consume($invalid);
                self::fail('Invalid provider sequence must fail.');
            } catch (OpenAiResponsesInvocationException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-OPENAI-RESPONSES-1716', 'KGN-OPENAI-RESPONSES-1717']);
                self::assertStringNotContainsString('annotation-sentinel', (string) $failure);
                self::assertStringNotContainsString('conflicting-text', (string) $failure);
            }
        }

        $failed = str_replace(
            '"message":"private provider failure"',
            '"details":"private-details-sentinel","message":"private provider failure"',
            OpenAiResponsesAdapterFixture::streamBody('failed-text-stream.sse'),
        );
        try {
            $this->consume($failed);
            self::fail('Unsupported provider error details must fail.');
        } catch (OpenAiResponsesInvocationException $failure) {
            self::assertSame('KGN-OPENAI-RESPONSES-1716', $failure->diagnosticId());
            self::assertStringNotContainsString('private-details-sentinel', (string) $failure);
            self::assertStringNotContainsString('private provider failure', (string) $failure);
        }
    }

    public function testProviderEventCeilingAndDeltaAggregationAreExact(): void
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $machine = new ResponsesSseStateMachine($fixture->source->request(), $fixture->openAiProfile);
        $responseId = 'resp_boundary';
        $messageId = 'msg_boundary';
        $machine->accept($this->event(ResponsesSseEventKind::ResponseCreated, 0, ['response' => $this->snapshot($responseId, 'in_progress')]));
        $machine->accept($this->event(ResponsesSseEventKind::ResponseInProgress, 1, ['response' => $this->snapshot($responseId, 'in_progress')]));
        $machine->accept($this->event(ResponsesSseEventKind::OutputItemAdded, 2, ['item' => ['content' => [], 'id' => $messageId, 'role' => 'assistant', 'status' => 'in_progress', 'type' => 'message'], 'output_index' => 0]));
        $machine->accept($this->event(ResponsesSseEventKind::ContentPartAdded, 3, ['content_index' => 0, 'item_id' => $messageId, 'output_index' => 0, 'part' => $this->part('')]));
        $text = '';
        for ($sequence = 4; $sequence <= 4091; ++$sequence) {
            $text .= 'x';
            self::assertSame('delta', $machine->accept($this->event(ResponsesSseEventKind::OutputTextDelta, $sequence, ['content_index' => 0, 'delta' => 'x', 'item_id' => $messageId, 'output_index' => 0])));
        }
        $machine->accept($this->event(ResponsesSseEventKind::OutputTextDone, 4092, ['content_index' => 0, 'item_id' => $messageId, 'logprobs' => [], 'output_index' => 0, 'text' => $text]));
        $machine->accept($this->event(ResponsesSseEventKind::ContentPartDone, 4093, ['content_index' => 0, 'item_id' => $messageId, 'output_index' => 0, 'part' => $this->part($text)]));
        $machine->accept($this->event(ResponsesSseEventKind::OutputItemDone, 4094, ['item' => ['content' => [$this->part($text)], 'id' => $messageId, 'role' => 'assistant', 'status' => 'completed', 'type' => 'message'], 'output_index' => 0]));
        $terminal = ['id' => $responseId, 'incomplete_details' => null, 'model' => 'openai-test-model', 'object' => 'response', 'output' => [['content' => [['annotations' => [], 'text' => $text, 'type' => 'output_text']], 'id' => $messageId, 'role' => 'assistant', 'status' => 'completed', 'type' => 'message']], 'status' => 'completed', 'usage' => null];
        self::assertSame('terminal', $machine->accept($this->event(ResponsesSseEventKind::ResponseCompleted, 4095, ['response' => $terminal])));

        $this->expectException(OpenAiResponsesInvocationException::class);
        $machine->accept($this->event(ResponsesSseEventKind::ResponseCompleted, 4096, ['response' => $terminal]));
    }

    /** @return array{ResponsesSseStateMachine, list<string>} */
    private function consume(string $body): array
    {
        $fixture = new OpenAiResponsesAdapterFixture(streaming: true);
        $reader = new ResponsesSseFrameReader(new Response(200, ['Content-Type' => 'text/event-stream'], (new StreamFactory())->createStream($body)));
        $machine = new ResponsesSseStateMachine($fixture->source->request(), $fixture->openAiProfile);
        $actions = [];
        try {
            while (null !== ($frame = $reader->next())) {
                $actions[] = $machine->accept((new ResponsesSseEventDecoder())->decode($frame));
            }
        } finally {
            $reader->close();
        }

        return [$machine, $actions];
    }

    /** @param array<string, mixed> $members */
    private function event(ResponsesSseEventKind $kind, int $sequence, array $members): ResponsesSseEvent
    {
        return new ResponsesSseEvent($kind, $sequence, ['sequence_number' => $sequence, 'type' => $kind->value, ...$members]);
    }

    /** @return array<string, mixed> */
    private function snapshot(string $id, string $status): array
    {
        return ['id' => $id, 'model' => 'openai-test-model', 'object' => 'response', 'output' => [], 'status' => $status];
    }

    /** @return array<string, mixed> */
    private function part(string $text): array
    {
        return ['annotations' => [], 'logprobs' => [], 'text' => $text, 'type' => 'output_text'];
    }
}
