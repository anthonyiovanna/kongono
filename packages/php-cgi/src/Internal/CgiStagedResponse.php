<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Http\Message\Message\Stream;

/** Owns one detached, fully validated response snapshot and its staging stream. */
#[Api(ApiStatus::Internal)]
final readonly class CgiStagedResponse
{
    /**
     * @param list<array{name: string, values: list<string>}> $headers
     */
    public function __construct(
        public int $status,
        public string $protocol,
        public array $headers,
        public Stream $body,
        public int $length,
    ) {}
}
