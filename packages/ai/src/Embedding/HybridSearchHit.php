<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Unified ranked result returned by {@see HybridSearch} combining full-text and semantic vector search via RRF. */
final readonly class HybridSearchHit
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public float $score,
        public ?float $keywordScore = null,
        public ?float $vectorScore = null,
        public string $title = '',
        public string $snippet = '',
        public string $content = '',
        public array $metadata = [],
    ) {}
}
