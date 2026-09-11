<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Stream;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/** Pulls bounded SSE frames from one owned response body without read-ahead. */
#[Api(ApiStatus::Internal)]
final class ResponsesSseFrameReader
{
    public const int READ_BYTES = 8192;
    public const int MAXIMUM_TOTAL_BYTES = 2097152;
    public const int MAXIMUM_RETAINED_UNREAD_BYTES = 262144;
    public const int MAXIMUM_LINE_BYTES = 262144;
    public const int MAXIMUM_DATA_BYTES = 262144;

    private StreamInterface $body;
    private string $buffer = '';
    private int $totalBytes = 0;
    private bool $closed = false;
    private bool $firstLine = true;

    public function __construct(ResponseInterface $response)
    {
        try {
            $body = $response->getBody();
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::streamingFraming('body_unavailable', previous: $failure);
        }
        $this->body = $body;
        try {
            $this->validateResponse($response);
        } catch (Throwable $failure) {
            $primary = $failure instanceof OpenAiResponsesInvocationException
                ? $failure
                : OpenAiResponsesInvocationException::streamingFraming('body_unavailable', previous: $failure);
            try {
                $this->close();
            } catch (Throwable $cleanup) {
                throw OpenAiResponsesInvocationException::streamingCleanup($primary, $this->cleanupCause($cleanup));
            }
            throw $primary;
        }
    }

    public function next(): ?ResponsesSseFrame
    {
        $event = null;
        $data = null;
        while (true) {
            $line = $this->line();
            if (null === $line) {
                if (null === $event && null === $data) {
                    return null;
                }
                throw OpenAiResponsesInvocationException::streamingFraming('unterminated_frame');
            }
            if ('' === $line) {
                if (null === $data) {
                    throw OpenAiResponsesInvocationException::streamingFraming('empty_frame');
                }

                return new ResponsesSseFrame($event, $data);
            }
            if (str_starts_with($line, ':')) {
                throw OpenAiResponsesInvocationException::streamingFraming('comment');
            }
            $colon = strpos($line, ':');
            $field = false === $colon ? $line : substr($line, 0, $colon);
            $value = false === $colon ? '' : substr($line, $colon + 1);
            if (str_starts_with($value, ' ')) {
                $value = substr($value, 1);
            }
            if (1 === preg_match('/[\x00-\x1F\x7F]/', $field)) {
                throw OpenAiResponsesInvocationException::streamingFraming('field_name');
            }
            if ('event' === $field) {
                if (null !== $event || null !== $data || '' === $value) {
                    throw OpenAiResponsesInvocationException::streamingFraming('event_field');
                }
                $event = $value;
            } elseif ('data' === $field) {
                if (null !== $data || '' === $value || strlen($value) > self::MAXIMUM_DATA_BYTES) {
                    throw OpenAiResponsesInvocationException::streamingFraming('data_field', self::MAXIMUM_DATA_BYTES);
                }
                $data = $value;
            } else {
                throw OpenAiResponsesInvocationException::streamingFraming('unknown_field');
            }
        }
    }

    public function assertTerminalEof(): void
    {
        if ('' !== $this->buffer) {
            throw OpenAiResponsesInvocationException::streamingFraming('trailing_bytes');
        }
        if (!$this->eof()) {
            $chunk = $this->read();
            if ('' !== $chunk) {
                throw OpenAiResponsesInvocationException::streamingFraming('trailing_bytes');
            }
        }
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        try {
            $this->body->close();
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::streamingCleanup(null, $failure);
        } finally {
            $this->buffer = '';
        }
    }

    private function validateResponse(ResponseInterface $response): void
    {
        if (200 !== $response->getStatusCode()) {
            throw OpenAiResponsesInvocationException::streamingFraming('status', observedCount: max(0, min(999, $response->getStatusCode())));
        }
        $values = $response->getHeader('Content-Type');
        if (1 !== count($values)) {
            throw OpenAiResponsesInvocationException::streamingFraming([] === $values ? 'content_type_missing' : 'content_type_duplicate');
        }
        if (1 !== preg_match('/\Atext\/event-stream(?:[ \t]*;[ \t]*charset[ \t]*=[ \t]*(?:utf-8|"utf-8"))?[ \t]*\z/iD', $values[0])) {
            throw OpenAiResponsesInvocationException::streamingFraming('content_type_invalid');
        }
        if (!$this->body->isReadable()) {
            throw OpenAiResponsesInvocationException::streamingFraming('body_unreadable');
        }
        $size = $this->body->getSize();
        if (null !== $size && $size > self::MAXIMUM_TOTAL_BYTES) {
            throw OpenAiResponsesInvocationException::streamingFraming('response_bytes', self::MAXIMUM_TOTAL_BYTES, $size);
        }
    }

    private function line(): ?string
    {
        while (true) {
            $newline = strpos($this->buffer, "\n");
            if (false !== $newline) {
                break;
            }
            if (strlen($this->buffer) > self::MAXIMUM_LINE_BYTES) {
                throw OpenAiResponsesInvocationException::streamingFraming('line_bytes', self::MAXIMUM_LINE_BYTES, strlen($this->buffer));
            }
            if ($this->eof()) {
                if ('' === $this->buffer) {
                    return null;
                }
                if (str_contains($this->buffer, "\r")) {
                    throw OpenAiResponsesInvocationException::streamingFraming('bare_cr');
                }
                $line = $this->buffer;
                $this->buffer = '';

                return $line;
            }
            $remaining = self::MAXIMUM_LINE_BYTES + 2 - strlen($this->buffer);
            $this->buffer .= $this->read(max(1, min(self::READ_BYTES, $remaining)));
            if (!str_contains($this->buffer, "\n") && strlen($this->buffer) > self::MAXIMUM_RETAINED_UNREAD_BYTES) {
                throw OpenAiResponsesInvocationException::streamingFraming('retained_unread_bytes', self::MAXIMUM_RETAINED_UNREAD_BYTES, strlen($this->buffer));
            }
        }
        $line = substr($this->buffer, 0, $newline);
        $this->buffer = substr($this->buffer, $newline + 1);
        if (str_ends_with($line, "\r")) {
            $line = substr($line, 0, -1);
        } elseif (str_contains($line, "\r")) {
            throw OpenAiResponsesInvocationException::streamingFraming('bare_cr');
        }
        if (strlen($line) > self::MAXIMUM_LINE_BYTES) {
            throw OpenAiResponsesInvocationException::streamingFraming('line_bytes', self::MAXIMUM_LINE_BYTES, strlen($line));
        }
        if (1 !== preg_match('//u', $line)) {
            throw OpenAiResponsesInvocationException::streamingFraming('utf8');
        }
        if ($this->firstLine && str_starts_with($line, "\xEF\xBB\xBF")) {
            throw OpenAiResponsesInvocationException::streamingFraming('bom');
        }
        $this->firstLine = false;

        return $line;
    }

    private function read(int $length = self::READ_BYTES): string
    {
        try {
            $chunk = $this->body->read($length);
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::streamingFraming('body_read', previous: $failure);
        }
        if ('' === $chunk && !$this->eof()) {
            throw OpenAiResponsesInvocationException::streamingFraming('body_stalled');
        }
        $this->totalBytes += strlen($chunk);
        if ($this->totalBytes > self::MAXIMUM_TOTAL_BYTES) {
            throw OpenAiResponsesInvocationException::streamingFraming('response_bytes', self::MAXIMUM_TOTAL_BYTES, $this->totalBytes);
        }

        return $chunk;
    }

    /** @phpstan-impure */
    private function eof(): bool
    {
        try {
            return $this->body->eof();
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::streamingFraming('body_read', previous: $failure);
        }
    }

    private function cleanupCause(Throwable $failure): Throwable
    {
        if ($failure instanceof OpenAiResponsesInvocationException && null !== $failure->cleanupFailure()) {
            return $failure->cleanupFailure();
        }

        return $failure;
    }
}
