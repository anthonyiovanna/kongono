<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

use Kongono\Database\Search\SearchEngineInterface;

/**
 * Reciprocal Rank Fusion (RRF) hybrid search combining exact keyword full-text search
 * (e.g. `PostgresFullTextSearch` / `MemorySearchEngine`) with semantic vector search
 * (e.g. `PgVectorStore` / `MemoryVectorStore`) for high-precision RAG retrieval.
 */
final readonly class HybridSearch
{
    public function __construct(
        private SearchEngineInterface $keywordEngine,
        private VectorStoreInterface $vectorStore,
        private int $rrfK = 60,
        private float $keywordWeight = 1.0,
        private float $vectorWeight = 1.0,
    ) {}

    /**
     * Execute both keyword and semantic vector searches and fuse rankings via Reciprocal Rank Fusion.
     *
     * @param list<float>          $queryVector
     * @param positive-int         $limit
     * @param array<string, mixed> $metadataFilter
     * @return list<HybridSearchHit>
     */
    public function search(
        string $queryText,
        array $queryVector,
        int $limit = 10,
        array $metadataFilter = [],
    ): array {
        $fetchLimit = max(1, $limit * 3);
        $k = max(1, $this->rrfK);

        $kwHits = $this->keywordEngine->search($queryText, $fetchLimit, $metadataFilter);
        $vecHits = $this->vectorStore->search($queryVector, $fetchLimit, $metadataFilter);

        /**
         * @var array<string, array{
         *     score: float,
         *     keywordScore: ?float,
         *     vectorScore: ?float,
         *     title: string,
         *     snippet: string,
         *     content: string,
         *     metadata: array<string, mixed>
         * }> $fused
         */
        $fused = [];

        foreach ($kwHits as $idx => $hit) {
            $rank = $idx + 1;
            $fused[$hit->id] = [
                'score' => $this->keywordWeight / ($k + $rank),
                'keywordScore' => $hit->score,
                'vectorScore' => null,
                'title' => $hit->title,
                'snippet' => $hit->snippet,
                'content' => '',
                'metadata' => $hit->metadata,
            ];
        }

        foreach ($vecHits as $idx => $hit) {
            $rank = $idx + 1;
            $id = $hit->document->id;
            $rrfDelta = $this->vectorWeight / ($k + $rank);

            if (isset($fused[$id])) {
                $fused[$id]['score'] += $rrfDelta;
                $fused[$id]['vectorScore'] = $hit->score;
                $fused[$id]['content'] = $hit->document->content;
                if ([] === $fused[$id]['metadata']) {
                    $fused[$id]['metadata'] = $hit->document->metadata;
                }
            } else {
                $fused[$id] = [
                    'score' => $rrfDelta,
                    'keywordScore' => null,
                    'vectorScore' => $hit->score,
                    'title' => is_string($hit->document->metadata['title'] ?? null) ? $hit->document->metadata['title'] : '',
                    'snippet' => '',
                    'content' => $hit->document->content,
                    'metadata' => $hit->document->metadata,
                ];
            }
        }

        $results = [];
        foreach ($fused as $id => $entry) {
            $results[] = new HybridSearchHit(
                id: $id,
                score: round($entry['score'], 6),
                keywordScore: $entry['keywordScore'],
                vectorScore: $entry['vectorScore'],
                title: $entry['title'],
                snippet: $entry['snippet'],
                content: $entry['content'],
                metadata: $entry['metadata'],
            );
        }

        usort($results, static fn(HybridSearchHit $a, HybridSearchHit $b): int => $b->score <=> $a->score);

        return array_slice($results, 0, max(1, $limit));
    }
}
