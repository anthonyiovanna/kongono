<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Psr\Http\Message\StreamInterface;
use SensitiveParameter;

/** Consumes a request body once from its admitted current cursor. */
#[Api(ApiStatus::Internal)]
final class CurlRequestBodyReader
{
    private int $consumed = 0;

    public function __construct(
        #[SensitiveParameter]
        private readonly StreamInterface $stream,
        private readonly int $remainingBytes,
        private readonly int $requestHeadBytes,
    ) {
        if ($remainingBytes < 0 || $requestHeadBytes < 0) {
            throw InvalidCurlTransportProfileException::request('request_budget');
        }
    }

    public function read(int $maximumBytes): string
    {
        if ($maximumBytes < 1 || $this->consumed >= $this->remainingBytes) {
            return '';
        }
        $length = min($maximumBytes, $this->remainingBytes - $this->consumed);
        try {
            $chunk = $this->stream->read($length);
        } catch (\Throwable) {
            throw InvalidCurlTransportProfileException::request('body_read');
        }
        if ('' === $chunk && $this->consumed < $this->remainingBytes) {
            throw InvalidCurlTransportProfileException::request('body_read');
        }
        $this->consumed += strlen($chunk);

        return $chunk;
    }

    public function remainingBytes(): int
    {
        return $this->remainingBytes;
    }

    public function consumedBytes(): int
    {
        return $this->consumed;
    }

    public function observedRequestBytes(): int
    {
        return $this->requestHeadBytes + $this->consumed;
    }
}
