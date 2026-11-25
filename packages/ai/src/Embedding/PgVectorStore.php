<?php

declare(strict_types=1);

namespace Kongono\Ai\Embedding;

use Kongono\Ai\AiException;
use Kongono\Database\ConnectionInterface;

/**
 * PostgreSQL `pgvector` vector store using parameterized cosine distance (`<=>`)
 * and JSONB metadata containment filtering (`@>`).
 */
final readonly class PgVectorStore implements VectorStoreInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'kongono_embeddings',
    ) {}

    public function ensureTable(int $dimensions = 1536): void
    {
        $dims = max(1, $dimensions);
        $dialect = $this->db->dialect();
        $quoted = $dialect->quoteIdentifier($this->table);
        $safeTableIdx = str_replace('.', '_', $this->table);
        $this->db->execute('CREATE EXTENSION IF NOT EXISTS vector');
        $this->db->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS %s ('
            . 'id VARCHAR(255) PRIMARY KEY, '
            . 'content TEXT NOT NULL, '
            . 'embedding vector(%d) NOT NULL, '
            . 'metadata JSONB NOT NULL DEFAULT \'{}\'::jsonb, '
            . 'updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()'
            . ')',
            $quoted,
            $dims,
        ));
        // Create HNSW cosine similarity index (pgvector supports HNSW up to 2,000 vector dimensions)
        if ($dims <= 2000) {
            $hnswIdx = $dialect->quoteIdentifier('idx_' . $safeTableIdx . '_embedding_hnsw');
            $this->db->execute(sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s USING hnsw (embedding vector_cosine_ops)',
                $hnswIdx,
                $quoted,
            ));
        }
        // Create GIN index on JSONB metadata for fast hybrid `@>` filtered semantic search
        $ginIdx = $dialect->quoteIdentifier('idx_' . $safeTableIdx . '_metadata_gin');
        $this->db->execute(sprintf(
            'CREATE INDEX IF NOT EXISTS %s ON %s USING gin (metadata)',
            $ginIdx,
            $quoted,
        ));
    }

    public function upsert(VectorDocument $document): void
    {
        $quoted = $this->db->dialect()->quoteIdentifier($this->table);
        $vectorLiteral = $this->formatVector($document->vector);
        $metaJson = json_encode($document->metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $this->db->execute(
            sprintf(
                'INSERT INTO %s (id, content, embedding, metadata, updated_at) '
                . 'VALUES (?, ?, ?::vector, ?::jsonb, NOW()) '
                . 'ON CONFLICT (id) DO UPDATE SET '
                . 'content = EXCLUDED.content, embedding = EXCLUDED.embedding, metadata = EXCLUDED.metadata, updated_at = NOW()',
                $quoted,
            ),
            [$document->id, $document->content, $vectorLiteral, $metaJson],
        );
    }

    public function search(array $queryVector, int $limit = 5, array $metadataFilter = []): array
    {
        $quoted = $this->db->dialect()->quoteIdentifier($this->table);
        $vectorLiteral = $this->formatVector($queryVector);
        $safeLimit = min(1000, max(1, $limit));
        $bindings = [$vectorLiteral];
        $whereSql = '';

        if ([] !== $metadataFilter) {
            $whereSql = ' WHERE metadata @> ?::jsonb';
            $bindings[] = json_encode($metadataFilter, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }

        $bindings[] = $vectorLiteral;
        $bindings[] = $safeLimit;

        $rows = $this->db->query(
            sprintf(
                'SELECT id, content, embedding::text AS embedding, metadata::text AS metadata, '
                . '1 - (embedding <=> ?::vector) AS score '
                . 'FROM %s%s ORDER BY embedding <=> ?::vector ASC LIMIT ?',
                $quoted,
                $whereSql,
            ),
            $bindings,
        );

        $hits = [];
        foreach ($rows as $row) {
            $id = is_string($row['id'] ?? null) ? $row['id'] : '';
            $content = is_string($row['content'] ?? null) ? $row['content'] : '';
            $vec = is_string($row['embedding'] ?? null) ? $this->parseVector($row['embedding']) : [];
            $metaRaw = is_string($row['metadata'] ?? null) ? json_decode($row['metadata'], true) : [];
            /** @var array<string, mixed> $meta */
            $meta = is_array($metaRaw) ? $metaRaw : [];
            $score = is_numeric($row['score'] ?? null) ? (float) $row['score'] : 0.0;

            $hits[] = new SearchHit(new VectorDocument($id, $content, $vec, $meta), $score);
        }

        return $hits;
    }

    public function delete(string $id): void
    {
        $quoted = $this->db->dialect()->quoteIdentifier($this->table);
        $this->db->execute(sprintf('DELETE FROM %s WHERE id = ?', $quoted), [$id]);
    }

    /** Delete the base document ID and any `#chunk-*` entries associated with `$documentId`. */
    public function deleteByDocumentId(string $documentId): void
    {
        $quoted = $this->db->dialect()->quoteIdentifier($this->table);
        $escapedPrefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $documentId) . '#chunk-%';
        $this->db->execute(
            sprintf("DELETE FROM %s WHERE id = ? OR id LIKE ? ESCAPE '\\'", $quoted),
            [$documentId, $escapedPrefix],
        );
    }

    /** @param list<float> $vector */
    private function formatVector(array $vector): string
    {
        if ([] === $vector) {
            throw new AiException('Vector must not be empty.');
        }

        $formatted = [];
        foreach ($vector as $v) {
            if (!is_finite($v)) {
                throw new AiException('Vector components must be finite numbers (NaN and INF are not allowed).');
            }
            $formatted[] = sprintf('%.8F', $v);
        }

        return '[' . implode(',', $formatted) . ']';
    }

    /** @return list<float> */
    private function parseVector(string $literal): array
    {
        $trimmed = trim($literal, "[] \t\n\r");
        if ('' === $trimmed) {
            return [];
        }

        return array_map(static fn(string $p): float => (float) trim($p), explode(',', $trimmed));
    }
}
