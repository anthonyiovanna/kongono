<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Stream;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Psr\Http\Message\StreamInterface;
use SensitiveParameter;

/** Bounds callback evidence and retains only an in-memory response body. */
#[Api(ApiStatus::Internal)]
final class CurlResponseAccumulator
{
    /** @var resource|null */
    private mixed $body;
    /** @var list<string> */
    private array $headerChunks = [];
    private int $headerBytes = 0;
    private int $headerCount = 0;
    private int $bodyBytes = 0;
    private ?string $violation = null;

    public function __construct(#[SensitiveParameter] private readonly EgressBudget $budget)
    {
        $body = fopen('php://memory', 'w+b');
        if (false === $body) {
            throw InvalidCurlTransportProfileException::request('stream_state');
        }
        $this->body = $body;
    }

    public function appendHeader(#[SensitiveParameter] string $chunk): int
    {
        $length = strlen($chunk);
        if ("\r\n" !== $chunk) {
            ++$this->headerCount;
        }
        if (
            $this->headerCount > $this->budget->maximumHeaderCount() + 16
            || !$this->within($this->headerBytes, $length, $this->budget->maximumAggregateHeaderBytes())
            || !$this->within($this->headerBytes + $this->bodyBytes, $length, $this->budget->maximumResponseBytes())
            || !$this->within($this->headerBytes, $length, $this->budget->maximumRetainedEvidenceBytes())
        ) {
            $this->violation = 'header_budget';
            return 0;
        }
        $this->headerChunks[] = $chunk;
        $this->headerBytes += $length;

        return $length;
    }

    public function appendBody(#[SensitiveParameter] string $chunk): int
    {
        $length = strlen($chunk);
        if (
            !$this->within($this->headerBytes + $this->bodyBytes, $length, $this->budget->maximumResponseBytes())
            || !$this->within($this->bodyBytes, $length, $this->budget->maximumBufferedBytes())
        ) {
            $this->violation = 'response_budget';
            return 0;
        }
        if (null === $this->body || $length !== fwrite($this->body, $chunk)) {
            $this->violation = 'stream_state';
            return 0;
        }
        $this->bodyBytes += $length;

        return $length;
    }

    public function headerBlock(): CurlResponseHeaderBlock
    {
        return (new CurlHeaderParser())->parse($this->headerChunks, $this->headerBytes);
    }

    public function releaseBody(): StreamInterface
    {
        if (null === $this->body || 0 !== fseek($this->body, 0)) {
            throw InvalidCurlTransportProfileException::request('stream_state');
        }
        $body = $this->body;
        $this->body = null;

        return new Stream($body);
    }

    public function responseBytes(): int
    {
        return $this->headerBytes + $this->bodyBytes;
    }

    public function bufferedBytes(): int
    {
        return $this->bodyBytes;
    }

    public function retainedEvidenceBytes(): int
    {
        return $this->headerBytes;
    }

    public function violation(): ?string
    {
        return $this->violation;
    }

    public function close(): void
    {
        if (null !== $this->body) {
            fclose($this->body);
            $this->body = null;
        }
    }

    private function within(int $current, int $addition, int $limit): bool
    {
        return $addition >= 0 && $current <= $limit - $addition;
    }
}
