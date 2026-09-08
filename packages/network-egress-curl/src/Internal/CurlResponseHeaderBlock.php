<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Stores one accepted final HTTP/1.1 status and header block. */
#[Api(ApiStatus::Internal)]
final readonly class CurlResponseHeaderBlock
{
    /** @var array<string, list<string>> */
    private array $headers;

    /** @param array<string, list<string>> $headers */
    public function __construct(
        private int $statusCode,
        private string $reasonPhrase,
        array $headers,
        private int $retainedBytes,
    ) {
        $this->headers = array_map(static fn(array $values): array => [...$values], $headers);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function reasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /** @return array<string, list<string>> */
    public function headers(): array
    {
        return array_map(static fn(array $values): array => [...$values], $this->headers);
    }

    public function retainedBytes(): int
    {
        return $this->retainedBytes;
    }
}
