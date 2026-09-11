<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Stream;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Internal\ProtocolGuard;

/** Strictly decodes one bounded SSE data object without retaining frame bytes. */
#[Api(ApiStatus::Internal)]
final class ResponsesSseEventDecoder
{
    public const int MAXIMUM_DEPTH = 64;

    public function decode(ResponsesSseFrame $frame): ResponsesSseEvent
    {
        try {
            ProtocolGuard::assertNoDuplicateMembers($frame->data());
            $decoded = json_decode($frame->data(), true, self::MAXIMUM_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw OpenAiResponsesInvocationException::streamingMapping('json', previous: $failure);
        } catch (\Throwable $failure) {
            if ($failure instanceof OpenAiResponsesInvocationException) {
                throw $failure;
            }
            throw OpenAiResponsesInvocationException::streamingMapping('duplicate_member', previous: $failure);
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw OpenAiResponsesInvocationException::streamingMapping('event_object');
        }
        $members = [];
        foreach ($decoded as $name => $value) {
            if (!is_string($name)) {
                throw OpenAiResponsesInvocationException::streamingMapping('event_object');
            }
            $members[$name] = $value;
        }
        $type = $members['type'] ?? null;
        $sequence = $members['sequence_number'] ?? null;
        if (!is_string($type) || !is_int($sequence) || $sequence < 0 || $sequence > 4095) {
            throw OpenAiResponsesInvocationException::streamingMapping('event_members');
        }
        $kind = ResponsesSseEventKind::tryFrom($type);
        if (null === $kind) {
            throw OpenAiResponsesInvocationException::streamingMapping('event_kind');
        }
        if (null !== $frame->event() && $frame->event() !== $type) {
            throw OpenAiResponsesInvocationException::streamingMapping('event_name', eventKind: $kind);
        }

        return new ResponsesSseEvent($kind, $sequence, $members);
    }
}
