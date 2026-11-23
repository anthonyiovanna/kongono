<?php

declare(strict_types=1);

namespace Kongono\Database\Search;

/** Ranked full-text search match returned by a {@see SearchEngineInterface}. */
final readonly class TextSearchHit
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public string $title,
        public float $score,
        public string $snippet = '',
        public array $metadata = [],
    ) {}
}
