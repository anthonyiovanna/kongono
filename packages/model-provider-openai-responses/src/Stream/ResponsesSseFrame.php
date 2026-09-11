<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;

/** Carries one bounded SSE frame before provider-event decoding. */
#[Api(ApiStatus::Internal)]
final readonly class ResponsesSseFrame
{
    public function __construct(private ?string $event, private string $data)
    {
        if ('' === $data || '[DONE]' === $data || strlen($data) > ResponsesSseFrameReader::MAXIMUM_DATA_BYTES) {
            throw OpenAiResponsesInvocationException::streamingFraming('data_value', ResponsesSseFrameReader::MAXIMUM_DATA_BYTES);
        }
    }

    public function event(): ?string
    {
        return $this->event;
    }

    public function data(): string
    {
        return $this->data;
    }
}
