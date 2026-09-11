<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/** Validates, consumes, and closes exactly one bounded terminal response body. */
#[Api(ApiStatus::Internal)]
final class OpenAiResponsesResponseBodyReader
{
    private const int CHUNK_BYTES = 8192;

    public function read(ResponseInterface $response): string
    {
        try {
            $body = $response->getBody();
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::responseBody('body_unavailable', $failure);
        }

        $primary = null;
        $cleanup = null;
        $bytes = '';
        try {
            $this->validateResponse($response, $body);
            while (!$this->atEnd($body)) {
                $remaining = ResponsesResponseDecoder::MAXIMUM_BODY_BYTES - strlen($bytes);
                $chunk = $body->read(min(self::CHUNK_BYTES, $remaining + 1));
                if ('' === $chunk && !$this->atEnd($body)) {
                    throw OpenAiResponsesInvocationException::responseBody('body_stalled');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > ResponsesResponseDecoder::MAXIMUM_BODY_BYTES) {
                    throw OpenAiResponsesInvocationException::responseBody(
                        'body_size',
                        limit: ResponsesResponseDecoder::MAXIMUM_BODY_BYTES,
                    );
                }
            }
        } catch (Throwable $failure) {
            $primary = $failure instanceof OpenAiResponsesInvocationException
                ? $failure
                : OpenAiResponsesInvocationException::responseBody('body_read', $failure);
        } finally {
            try {
                $body->close();
            } catch (Throwable $failure) {
                $cleanup = $failure;
            }
        }

        if (null !== $cleanup) {
            if (null !== $primary) {
                throw OpenAiResponsesInvocationException::responseBody('combined_failure', $primary, $cleanup);
            }
            throw OpenAiResponsesInvocationException::responseBody('body_cleanup', cleanupFailure: $cleanup);
        }
        if (null !== $primary) {
            throw $primary;
        }

        return $bytes;
    }

    private function validateResponse(ResponseInterface $response, StreamInterface $body): void
    {
        if (200 !== $response->getStatusCode()) {
            throw OpenAiResponsesInvocationException::httpResponse('status', $response->getStatusCode());
        }
        $contentTypes = $response->getHeader('Content-Type');
        if ([] === $contentTypes) {
            throw OpenAiResponsesInvocationException::httpResponse('content_type_missing');
        }
        if (1 !== count($contentTypes)) {
            throw OpenAiResponsesInvocationException::httpResponse('content_type_duplicate');
        }
        if (1 !== preg_match('/\Aapplication\/json(?:[ \t]*;[ \t]*charset[ \t]*=[ \t]*(?:utf-8|"utf-8"))?[ \t]*\z/iD', $contentTypes[0])) {
            throw OpenAiResponsesInvocationException::httpResponse('content_type_invalid');
        }
        if (!$body->isReadable()) {
            throw OpenAiResponsesInvocationException::responseBody('body_unreadable');
        }
        $size = $body->getSize();
        if (null !== $size && $size > ResponsesResponseDecoder::MAXIMUM_BODY_BYTES) {
            throw OpenAiResponsesInvocationException::responseBody(
                'body_size',
                limit: ResponsesResponseDecoder::MAXIMUM_BODY_BYTES,
            );
        }
    }

    /** @phpstan-impure */
    private function atEnd(StreamInterface $body): bool
    {
        return $body->eof();
    }
}
