<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Ranked semantic search match returned by a VectorStoreInterface. */
final readonly class SearchHit
{
    public function __construct(
        public VectorDocument $document,
        public float $score,
    ) {}
}
