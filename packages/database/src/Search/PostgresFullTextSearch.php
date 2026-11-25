<?php

declare(strict_types=1);

namespace Kongono\Database\Search;

use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;

/**
 * Native PostgreSQL full-text search powered by generated weighted `tsvector` columns,
 * GIN indexes, `websearch_to_tsquery()`, `ts_rank_cd()`, `ts_headline()`, and JSONB `@>`
 * metadata filtering — zero Elasticsearch or Meilisearch sidecar required.
 */
final readonly class PostgresFullTextSearch implements SearchEngineInterface
{
    /** @var list<string> Strict allowlist of built-in PostgreSQL text-search dictionaries */
    private const array ALLOWED_CONFIGS = [
        'simple',
        'english',
        'french',
        'german',
        'spanish',
        'portuguese',
        'italian',
        'dutch',
        'russian',
        'swedish',
    ];

    private string $config;

    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'kongono_search_index',
        string $languageConfig = 'english',
    ) {
        if (Dialect::Postgres !== $db->dialect()) {
            throw new DatabaseException(sprintf(
                'PostgresFullTextSearch requires a PostgreSQL connection (got "%s").',
                $db->dialect()->value,
            ));
        }

        $cleanConfig = strtolower(trim($languageConfig));
        if (!in_array($cleanConfig, self::ALLOWED_CONFIGS, true)) {
            throw new DatabaseException(sprintf(
                'Unsupported PostgreSQL text-search config "%s" (allowed: %s).',
                $languageConfig,
                implode(', ', self::ALLOWED_CONFIGS),
            ));
        }
        $this->config = $cleanConfig;
    }

    /** Create the search index table, generated weighted `tsvector` column, and GIN indexes. */
    public function installSchema(): void
    {
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $safeTableName = str_replace('.', '_', $this->table);
        $tsvIdx = $this->db->dialect()->quoteIdentifier('idx_' . $safeTableName . '_tsv');
        $metaIdx = $this->db->dialect()->quoteIdentifier('idx_' . $safeTableName . '_meta');

        $this->db->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS %s ('
            . 'id VARCHAR(192) PRIMARY KEY, '
            . 'title TEXT NOT NULL, '
            . 'body TEXT NOT NULL, '
            . "metadata JSONB NOT NULL DEFAULT '{}'::jsonb, "
            . 'tsv tsvector GENERATED ALWAYS AS ('
            . "setweight(to_tsvector('%s', coalesce(title, '')), 'A') || "
            . "setweight(to_tsvector('%s', coalesce(body, '')), 'B')"
            . ') STORED, '
            . 'updated_at BIGINT NOT NULL'
            . ')',
            $t,
            $this->config,
            $this->config,
        ));

        $this->db->execute(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s USING GIN (tsv)', $tsvIdx, $t));
        $this->db->execute(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s USING GIN (metadata)', $metaIdx, $t));
    }

    public function index(string $id, string $title, string $body, array $metadata = []): void
    {
        if ('' === trim($id)) {
            throw new DatabaseException('Search document ID cannot be empty.');
        }

        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $encodedMeta = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES, 64);

        $this->db->execute(
            sprintf(
                'INSERT INTO %s (id, title, body, metadata, updated_at) VALUES (?, ?, ?, ?::jsonb, ?) '
                . 'ON CONFLICT (id) DO UPDATE SET title = EXCLUDED.title, body = EXCLUDED.body, metadata = EXCLUDED.metadata, updated_at = EXCLUDED.updated_at',
                $t,
            ),
            [$id, $title, $body, $encodedMeta, time()],
        );
    }

    public function remove(string $id): void
    {
        $this->db->table($this->table)->where('id', '=', $id)->delete();
    }

    public function search(string $query, int $limit = 10, array $metadataFilter = []): array
    {
        $cleanQuery = trim($query);
        if ('' === $cleanQuery) {
            return [];
        }

        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $safeLimit = min(1000, max(1, $limit));
        $bindings = [$cleanQuery];
        $whereFilter = '';

        if ([] !== $metadataFilter) {
            $whereFilter = ' AND s.metadata @> ?::jsonb';
            $bindings[] = json_encode($metadataFilter, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES, 32);
        }
        $bindings[] = $safeLimit;

        $sql = sprintf(
            'SELECT s.id, s.title, s.metadata, ts_rank_cd(s.tsv, q.tsq) AS score, '
            . "ts_headline('%s', s.body, q.tsq, 'MaxFragments=1, MaxWords=25, MinWords=6, StartSel=[[, StopSel=]]') AS snippet "
            . "FROM %s AS s, websearch_to_tsquery('%s', ?) AS q(tsq) "
            . 'WHERE s.tsv @@ q.tsq%s '
            . 'ORDER BY score DESC, s.updated_at DESC LIMIT ?',
            $this->config,
            $t,
            $this->config,
            $whereFilter,
        );

        $rows = $this->db->query($sql, $bindings);
        $hits = [];

        foreach ($rows as $row) {
            $id = is_string($row['id'] ?? null) ? $row['id'] : '';
            if ('' === $id) {
                continue;
            }
            $title = is_string($row['title'] ?? null) ? $row['title'] : '';
            $score = is_numeric($row['score'] ?? null) ? (float) $row['score'] : 0.0;
            $snippet = is_string($row['snippet'] ?? null) ? $row['snippet'] : '';

            /** @var array<string, mixed> $meta */
            $meta = [];
            $rawMeta = $row['metadata'] ?? null;
            if (is_string($rawMeta) && '' !== $rawMeta) {
                $decoded = json_decode($rawMeta, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) {
                        $meta[(string) $k] = $v;
                    }
                }
            } elseif (is_array($rawMeta)) {
                foreach ($rawMeta as $k => $v) {
                    $meta[(string) $k] = $v;
                }
            }

            $hits[] = new TextSearchHit(
                id: $id,
                title: $title,
                score: $score,
                snippet: $snippet,
                metadata: $meta,
            );
        }

        return $hits;
    }
}
