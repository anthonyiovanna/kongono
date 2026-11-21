<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Word-boundary overlapping text splitter for Retrieval-Augmented Generation (RAG). */
final readonly class TextChunker
{
    /**
     * @param positive-int     $chunkSize Target characters per chunk
     * @param non-negative-int $overlap   Overlapping trailing characters carried to the next chunk
     */
    public function __construct(
        private int $chunkSize = 800,
        private int $overlap = 120,
    ) {}

    /** @return list<string> */
    public function split(string $text): array
    {
        $normalized = trim((string) preg_replace("/\r\n?/", "\n", $text));
        if ('' === $normalized) {
            return [];
        }
        if (mb_strlen($normalized) <= $this->chunkSize) {
            return [$normalized];
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($words)) {
            return [$normalized];
        }

        $chunks = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = '' === $current ? $word : $current . ' ' . $word;
            if ('' !== $current && mb_strlen($candidate) > $this->chunkSize) {
                $chunks[] = $current;
                $tail = $this->overlap > 0 ? mb_substr($current, -$this->overlap) : '';
                $tail = ltrim($tail);
                $current = '' !== $tail ? $tail . ' ' . $word : $word;
            } else {
                $current = $candidate;
            }
        }

        $chunks[] = $current;

        return $chunks;
    }
}
