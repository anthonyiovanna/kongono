<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Internal\CanonicalJson;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Response\DecodedResponsesResponse;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;

/** Enforces the exact single-message text-event sequence and snapshot equality. */
#[Api(ApiStatus::Internal)]
final class ResponsesSseStateMachine
{
    private int $providerSequence = 0;
    private int $stage = 0;
    private int $providerEvents = 0;
    private ?string $responseId = null;
    private ?string $messageId = null;
    private string $text = '';
    private ?DecodedResponsesResponse $terminalResponse = null;
    private bool $failed = false;

    public function __construct(
        private readonly InvocationRequest $request,
        private readonly OpenAiResponsesProfile $profile,
    ) {}

    /** @return 'started'|'delta'|'terminal'|'failed'|'none' */
    public function accept(ResponsesSseEvent $event): string
    {
        ++$this->providerEvents;
        if ($this->providerEvents > 4096) {
            throw OpenAiResponsesInvocationException::streamingOrder('provider_events', 4096, $this->providerEvents);
        }
        if ($event->sequenceNumber() !== $this->providerSequence++) {
            throw OpenAiResponsesInvocationException::streamingOrder('provider_sequence', eventKind: $event->kind());
        }

        return match ($event->kind()) {
            ResponsesSseEventKind::ResponseCreated => $this->responseSnapshot($event, 0, 'in_progress', true),
            ResponsesSseEventKind::ResponseInProgress => $this->responseSnapshot($event, 1, 'in_progress'),
            ResponsesSseEventKind::OutputItemAdded => $this->itemAdded($event),
            ResponsesSseEventKind::ContentPartAdded => $this->partAdded($event),
            ResponsesSseEventKind::OutputTextDelta => $this->delta($event),
            ResponsesSseEventKind::OutputTextDone => $this->textDone($event),
            ResponsesSseEventKind::ContentPartDone => $this->partDone($event),
            ResponsesSseEventKind::OutputItemDone => $this->itemDone($event),
            ResponsesSseEventKind::ResponseCompleted,
            ResponsesSseEventKind::ResponseIncomplete => $this->terminal($event),
            ResponsesSseEventKind::ResponseFailed => $this->failed($event),
        };
    }

    public function deltaText(ResponsesSseEvent $event): string
    {
        $delta = $event->members()['delta'] ?? null;
        if (!is_string($delta)) {
            throw OpenAiResponsesInvocationException::streamingMapping('delta', eventKind: $event->kind());
        }

        return $delta;
    }

    public function terminalResponse(): ?DecodedResponsesResponse
    {
        return $this->terminalResponse;
    }

    public function isTerminal(): bool
    {
        return null !== $this->terminalResponse || $this->failed;
    }

    /** @return 'started'|'none' */
    private function responseSnapshot(ResponsesSseEvent $event, int $expectedStage, string $status, bool $created = false): string
    {
        $this->stage($expectedStage, $event);
        $members = $event->members();
        $this->keys($members, ['response', 'sequence_number', 'type'], $event->kind());
        $response = $this->object($members['response'] ?? null, $event->kind());
        $this->keys($response, ['id', 'model', 'object', 'output', 'status'], $event->kind());
        $id = $response['id'] ?? null;
        if (!is_string($id) || 1 !== preg_match('/\Aresp_[A-Za-z0-9_-]{1,128}\z/D', $id)
            || ($created ? null !== $this->responseId : $id !== $this->responseId)
            || ($response['status'] ?? null) !== $status
            || ($response['object'] ?? null) !== 'response'
            || ($response['model'] ?? null) !== $this->profile->neutralProfile()->modelIdentity()->value()
            || ($response['output'] ?? null) !== []
        ) {
            throw OpenAiResponsesInvocationException::streamingMapping('response_snapshot', eventKind: $event->kind());
        }
        if ($created) {
            $this->responseId = $id;
        }
        ++$this->stage;

        return $created ? 'started' : 'none';
    }

    /** @return 'none' */
    private function itemAdded(ResponsesSseEvent $event): string
    {
        $this->stage(2, $event);
        $m = $event->members();
        $this->keys($m, ['item', 'output_index', 'sequence_number', 'type'], $event->kind());
        $item = $this->object($m['item'] ?? null, $event->kind());
        $this->validateIndex($m, 'output_index', $event->kind());
        $this->keys($item, ['content', 'id', 'role', 'status', 'type'], $event->kind());
        $id = $item['id'] ?? null;
        if (!is_string($id) || 1 !== preg_match('/\Amsg_[A-Za-z0-9_-]{1,128}\z/D', $id)
            || 'message' !== ($item['type'] ?? null) || 'assistant' !== ($item['role'] ?? null)
            || 'in_progress' !== ($item['status'] ?? null) || [] !== ($item['content'] ?? null)
        ) {
            throw OpenAiResponsesInvocationException::streamingMapping('message_added', eventKind: $event->kind());
        }
        $this->messageId = $id;
        ++$this->stage;

        return 'none';
    }

    /** @return 'none' */
    private function partAdded(ResponsesSseEvent $event): string
    {
        $this->stage(3, $event);
        $m = $event->members();
        $this->keys($m, ['content_index', 'item_id', 'output_index', 'part', 'sequence_number', 'type'], $event->kind());
        $this->validateCoordinates($m, $event->kind());
        $part = $this->object($m['part'] ?? null, $event->kind());
        $this->validatePart($part, '', $event->kind());
        ++$this->stage;

        return 'none';
    }

    /** @return 'delta' */
    private function delta(ResponsesSseEvent $event): string
    {
        if ($this->stage < 4 || $this->stage > 4) {
            throw OpenAiResponsesInvocationException::streamingOrder('event_order', eventKind: $event->kind());
        }
        $m = $event->members();
        $this->keys($m, ['content_index', 'delta', 'item_id', 'output_index', 'sequence_number', 'type'], $event->kind());
        $this->validateCoordinates($m, $event->kind());
        $delta = $m['delta'] ?? null;
        if (!is_string($delta) || '' === $delta || 1 !== preg_match('//u', $delta)) {
            throw OpenAiResponsesInvocationException::streamingMapping('delta', eventKind: $event->kind());
        }
        $observed = strlen($this->text) + strlen($delta);
        $limit = $this->request->limits()->maxOutputBytes();
        if ($observed > $limit) {
            throw OpenAiResponsesInvocationException::streamingOrder('delta_bytes', $limit, $observed, $event->kind());
        }
        $this->text .= $delta;

        return 'delta';
    }

    /** @return 'none' */
    private function textDone(ResponsesSseEvent $event): string
    {
        $this->stage(4, $event);
        $m = $event->members();
        $this->keys($m, ['content_index', 'item_id', 'logprobs', 'output_index', 'sequence_number', 'text', 'type'], $event->kind());
        $this->validateCoordinates($m, $event->kind());
        if (($m['text'] ?? null) !== $this->text || [] !== ($m['logprobs'] ?? null)) {
            throw OpenAiResponsesInvocationException::streamingOrder('text_snapshot', eventKind: $event->kind());
        }
        ++$this->stage;

        return 'none';
    }

    /** @return 'none' */
    private function partDone(ResponsesSseEvent $event): string
    {
        $this->stage(5, $event);
        $m = $event->members();
        $this->keys($m, ['content_index', 'item_id', 'output_index', 'part', 'sequence_number', 'type'], $event->kind());
        $this->validateCoordinates($m, $event->kind());
        $this->validatePart($this->object($m['part'] ?? null, $event->kind()), $this->text, $event->kind());
        ++$this->stage;

        return 'none';
    }

    /** @return 'none' */
    private function itemDone(ResponsesSseEvent $event): string
    {
        $this->stage(6, $event);
        $m = $event->members();
        $this->keys($m, ['item', 'output_index', 'sequence_number', 'type'], $event->kind());
        $this->validateIndex($m, 'output_index', $event->kind());
        $item = $this->object($m['item'] ?? null, $event->kind());
        $this->keys($item, ['content', 'id', 'role', 'status', 'type'], $event->kind());
        $content = $item['content'] ?? null;
        if (($item['id'] ?? null) !== $this->messageId || 'message' !== ($item['type'] ?? null)
            || 'assistant' !== ($item['role'] ?? null) || !in_array($item['status'] ?? null, ['completed', 'incomplete'], true)
            || !is_array($content) || 1 !== count($content)
        ) {
            throw OpenAiResponsesInvocationException::streamingMapping('message_done', eventKind: $event->kind());
        }
        $this->validatePart($this->object($content[0] ?? null, $event->kind()), $this->text, $event->kind());
        ++$this->stage;

        return 'none';
    }

    /** @return 'terminal' */
    private function terminal(ResponsesSseEvent $event): string
    {
        $this->stage(7, $event);
        $m = $event->members();
        $this->keys($m, ['response', 'sequence_number', 'type'], $event->kind());
        $response = $this->object($m['response'] ?? null, $event->kind());
        $required = ResponsesSseEventKind::ResponseCompleted === $event->kind()
            ? ['id', 'model', 'object', 'output', 'status']
            : ['id', 'incomplete_details', 'model', 'object', 'output', 'status'];
        $this->keysWithin($response, $required, ['id', 'incomplete_details', 'model', 'object', 'output', 'status', 'usage'], $event->kind());
        try {
            $decoded = (new ResponsesResponseDecoder())->decode($this->request, $this->profile, CanonicalJson::encode($response));
        } catch (\Throwable $failure) {
            throw OpenAiResponsesInvocationException::streamingMapping('terminal_response', eventKind: $event->kind(), previous: $failure);
        }
        if ($decoded->responseId() !== $this->responseId || $decoded->text()->value() !== $this->text
            || $decoded->status()->value !== (ResponsesSseEventKind::ResponseCompleted === $event->kind() ? 'completed' : 'incomplete')
        ) {
            throw OpenAiResponsesInvocationException::streamingOrder('terminal_snapshot', eventKind: $event->kind());
        }
        $this->terminalResponse = $decoded;
        ++$this->stage;

        return 'terminal';
    }

    /** @return 'failed' */
    private function failed(ResponsesSseEvent $event): string
    {
        $this->stage(7, $event);
        $m = $event->members();
        $this->keys($m, ['response', 'sequence_number', 'type'], $event->kind());
        $response = $this->object($m['response'] ?? null, $event->kind());
        $this->keys($response, ['error', 'id', 'model', 'object', 'status'], $event->kind());
        $error = $this->object($response['error'] ?? null, $event->kind());
        $this->keys($error, ['code', 'message'], $event->kind());
        if (($response['id'] ?? null) !== $this->responseId || 'response' !== ($response['object'] ?? null) || 'failed' !== ($response['status'] ?? null)
            || ($response['model'] ?? null) !== $this->profile->neutralProfile()->modelIdentity()->value()
            || !is_string($error['code'] ?? null) || '' === $error['code']
            || !is_string($error['message'] ?? null) || '' === $error['message']
        ) {
            throw OpenAiResponsesInvocationException::streamingMapping('failed_response', eventKind: $event->kind());
        }
        $this->failed = true;
        ++$this->stage;

        return 'failed';
    }

    /** @param array<string, mixed> $members */
    private function validateCoordinates(array $members, ResponsesSseEventKind $kind): void
    {
        $this->validateIndex($members, 'output_index', $kind);
        $this->validateIndex($members, 'content_index', $kind);
        if (($members['item_id'] ?? null) !== $this->messageId) {
            throw OpenAiResponsesInvocationException::streamingMapping('message_identity', eventKind: $kind);
        }
    }

    /** @param array<string, mixed> $part */
    private function validatePart(array $part, string $text, ResponsesSseEventKind $kind): void
    {
        $this->keys($part, ['annotations', 'logprobs', 'text', 'type'], $kind);
        if ('output_text' !== ($part['type'] ?? null) || $text !== ($part['text'] ?? null)
            || [] !== ($part['annotations'] ?? null) || [] !== ($part['logprobs'] ?? null)
        ) {
            throw OpenAiResponsesInvocationException::streamingMapping('content_part', eventKind: $kind);
        }
    }

    /** @param array<string, mixed> $members */
    private function validateIndex(array $members, string $field, ResponsesSseEventKind $kind): void
    {
        if (!array_key_exists($field, $members) || 0 !== $members[$field]) {
            throw OpenAiResponsesInvocationException::streamingMapping('index', eventKind: $kind);
        }
    }

    private function stage(int $expected, ResponsesSseEvent $event): void
    {
        if ($this->stage !== $expected) {
            throw OpenAiResponsesInvocationException::streamingOrder('event_order', eventKind: $event->kind());
        }
    }

    /**
     * @param array<string, mixed> $object
     * @param list<string>         $keys
     */
    private function keys(array $object, array $keys, ResponsesSseEventKind $kind): void
    {
        $actual = array_keys($object);
        sort($actual, SORT_STRING);
        sort($keys, SORT_STRING);
        if ($actual !== $keys) {
            throw OpenAiResponsesInvocationException::streamingMapping('object_members', eventKind: $kind);
        }
    }

    /**
     * @param array<string, mixed> $object
     * @param list<string>         $required
     * @param list<string>         $allowed
     */
    private function keysWithin(array $object, array $required, array $allowed, ResponsesSseEventKind $kind): void
    {
        foreach (array_keys($object) as $name) {
            if (!in_array($name, $allowed, true)) {
                throw OpenAiResponsesInvocationException::streamingMapping('object_members', eventKind: $kind);
            }
        }
        foreach ($required as $name) {
            if (!array_key_exists($name, $object)) {
                throw OpenAiResponsesInvocationException::streamingMapping('object_members', eventKind: $kind);
            }
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, ResponsesSseEventKind $kind): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw OpenAiResponsesInvocationException::streamingMapping('object', eventKind: $kind);
        }

        $object = [];
        foreach ($value as $name => $member) {
            if (!is_string($name)) {
                throw OpenAiResponsesInvocationException::streamingMapping('object', eventKind: $kind);
            }
            $object[$name] = $member;
        }

        return $object;
    }
}
