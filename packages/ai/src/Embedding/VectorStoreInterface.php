<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Contract for semantic vector stores (in-memory, PostgreSQL pgvector, etc.). */
interface VectorStoreInterface
{
    public function upsert(VectorDocument $document): void;

    /**
     * Search for the top-$limit most similar documents using cosine similarity.
     *
     * @param list<float>          $queryVector
     * @param positive-int         $limit
     * @param array<string, mixed> $metadataFilter Exact-match metadata filter
     * @return list<SearchHit>
     */
    public function search(array $queryVector, int $limit = 5, array $metadataFilter = []): array;

    public function delete(string $id): void;
}
