<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Immutable document stored with its dense embedding vector and optional metadata. */
final readonly class VectorDocument
{
    /**
     * @param list<float>          $vector
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $id,
        public string $content,
        public array $vector,
        public array $metadata = [],
    ) {}
}
