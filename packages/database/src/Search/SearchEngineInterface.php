<?php

declare(strict_types=1);

namespace Kongono\Database\Search;

/** Contract for zero-dependency full-text search engines (PostgreSQL tsvector, in-memory tests). */
interface SearchEngineInterface
{
    /** @param array<string, mixed> $metadata */
    public function index(string $id, string $title, string $body, array $metadata = []): void;

    public function remove(string $id): void;

    /**
     * @param positive-int         $limit
     * @param array<string, mixed> $metadataFilter Exact-match metadata filter
     * @return list<TextSearchHit>
     */
    public function search(string $query, int $limit = 10, array $metadataFilter = []): array;
}
