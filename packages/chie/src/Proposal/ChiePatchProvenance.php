<?php

declare(strict_types=1);

namespace Kongono\Chie\Proposal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Exception\InvalidChieValueException;

/** Binds a candidate to the exact artifact source and replaced complete line range. */
#[Api(ApiStatus::Experimental)]
final readonly class ChiePatchProvenance
{
    public function __construct(
        private string $reference,
        private string $path,
        private string $authority,
        private string $sourceSha256,
        private int $lineStart,
        private int $lineEnd,
    ) {
        if (
            '' === $reference
            || '' === $path
            || 'current-explanatory-documentation' !== $authority
            || 1 !== preg_match('/^[A-F0-9]{64}$/D', $sourceSha256)
            || $lineStart < 1
            || $lineEnd < $lineStart
        ) {
            throw InvalidChieValueException::proposal();
        }
    }

    /** @return array{reference: string, path: string, authority: string, source_sha256: string, line_start: int, line_end: int} */
    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'path' => $this->path,
            'authority' => $this->authority,
            'source_sha256' => $this->sourceSha256,
            'line_start' => $this->lineStart,
            'line_end' => $this->lineEnd,
        ];
    }

    public function path(): string
    {
        return $this->path;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
}
