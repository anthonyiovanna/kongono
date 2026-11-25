<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

/** Pure PHP 8.5 in-memory cosine-similarity vector store (ideal for testing, CLI tools, and small corpora). */
final class MemoryVectorStore implements VectorStoreInterface
{
    /** @var array<string, VectorDocument> */
    private array $documents = [];

    public function upsert(VectorDocument $document): void
    {
        $this->documents[$document->id] = $document;
    }

    public function search(array $queryVector, int $limit = 5, array $metadataFilter = []): array
    {
        $hits = [];
        foreach ($this->documents as $doc) {
            if (!$this->matchesFilter($doc->metadata, $metadataFilter)) {
                continue;
            }
            $score = self::cosineSimilarity($queryVector, $doc->vector);
            $hits[] = new SearchHit($doc, $score);
        }

        usort($hits, static fn(SearchHit $a, SearchHit $b): int => $b->score <=> $a->score);

        return array_slice($hits, 0, max(1, $limit));
    }

    public function delete(string $id): void
    {
        unset($this->documents[$id]);
    }

    /** Delete the base document ID and any `#chunk-*` entries associated with `$documentId`. */
    public function deleteByDocumentId(string $documentId): void
    {
        $prefix = $documentId . '#chunk-';
        foreach (array_keys($this->documents) as $id) {
            if ($id === $documentId || str_starts_with($id, $prefix)) {
                unset($this->documents[$id]);
            }
        }
    }

    public function count(): int
    {
        return count($this->documents);
    }

    /**
     * Compute cosine similarity in [-1.0, 1.0].
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if (0 === $len) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $len; ++$i) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom > 0.0 ? $dot / $denom : 0.0;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $filter
     */
    private function matchesFilter(array $metadata, array $filter): bool
    {
        foreach ($filter as $key => $expected) {
            if (!array_key_exists($key, $metadata) || $metadata[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }
}
