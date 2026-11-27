<?php

declare(strict_types=1);

namespace Kongono\Database\Bulk;

use JsonException;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\DatabaseException;
use Kongono\Database\Dialect;
use Kongono\Database\PdoConnection;
use Stringable;

/**
 * High-throughput, O(1)-memory bulk loader & upsert writer for PostgreSQL (via `COPY FROM STDIN`
 * into a transaction-scoped `TEMP` staging table + `ON CONFLICT ... WHERE IS DISTINCT FROM` merge),
 * with automatic chunked parameterized multi-row fallback on SQLite and MySQL for seamless local
 * development and unit testing.
 *
 * Persistent-runtime safe: holds no static mutable state and streams arbitrary `iterable` generators
 * in bounded chunks.
 */
final readonly class PgCopyWriter
{
    private const string IDENT_PATTERN = '/\A[A-Za-z_][A-Za-z0-9_]*\z/';
    private const int SQLITE_MAX_VARS = 900;
    private const int MYSQL_MAX_VARS = 30_000;

    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    /**
     * Stream rows directly into `$table` in bounded `$chunkSize` chunks.
     *
     * @param list<string>                        $columns
     * @param iterable<int, array<string, mixed>> $rows
     */
    public function insert(
        string $table,
        array $columns,
        iterable $rows,
        int $chunkSize = 10_000,
    ): BulkUpsertResult {
        $this->assertValidTable($table);
        $this->assertValidColumns($columns);
        if ($chunkSize < 1) {
            throw new DatabaseException('Chunk size must be at least 1.');
        }

        $startNs = hrtime(true);
        $usePgCopy = $this->canUsePgCopy();
        $dialect = $this->connection->dialect();
        $effectiveChunkSize = $usePgCopy ? $chunkSize : $this->clampParameterizedChunkSize($dialect, count($columns), $chunkSize);

        $rowsProcessed = 0;
        $rowsAffected = 0;
        $chunks = 0;

        /** @var list<array<string, mixed>> $buffer */
        $buffer = [];

        $flush = function (array $batch) use ($table, $columns, $usePgCopy, &$rowsAffected, &$chunks): void {
            /** @var list<array<string, mixed>> $batch */
            if ([] === $batch) {
                return;
            }
            $chunks++;
            if ($usePgCopy && $this->connection instanceof PdoConnection) {
                $lines = [];
                foreach ($batch as $row) {
                    $lines[] = self::encodeCopyLine($row, $columns);
                }
                $quotedFields = implode(', ', array_map(
                    static fn(string $col): string => self::quoteIdent($col, Dialect::Postgres),
                    $columns,
                ));
                $this->connection->copyFromArray(
                    self::quoteTable($table, Dialect::Postgres),
                    $lines,
                    "\t",
                    '\\\\N',
                    $quotedFields,
                );
                $rowsAffected += count($batch);

                return;
            }

            $rowsAffected += $this->executeMultiRowInsert($table, $columns, $batch);
        };

        $this->connection->transaction(function () use ($rows, $effectiveChunkSize, &$buffer, &$rowsProcessed, $flush): void {
            foreach ($rows as $row) {
                $buffer[] = $row;
                $rowsProcessed++;
                if (count($buffer) >= $effectiveChunkSize) {
                    $flush($buffer);
                    $buffer = [];
                }
            }
            if ([] !== $buffer) {
                $flush($buffer);
                $buffer = [];
            }
        });

        return new BulkUpsertResult(
            rowsProcessed: $rowsProcessed,
            rowsAffected: $rowsAffected,
            chunks: $chunks,
            strategy: $usePgCopy ? 'pg_copy' : 'chunked_insert',
            durationMs: round((hrtime(true) - $startNs) / 1_000_000, 3),
        );
    }

    /**
     * Stream rows into `$table` with conflict-safe bulk upsert.
     *
     * On PostgreSQL: creates a narrow `TEMP` staging table containing ONLY `$columns` (avoiding `LIKE`
     * `NOT NULL` and generated-column traps on omitted columns), streams chunks via `COPY FROM STDIN`,
     * optionally deduplicates duplicate conflict keys (`DISTINCT ON (...) ORDER BY ..., ctid DESC`,
     * preventing SQLSTATE 21000), optionally merges + truncates per chunk (`$mergePerChunk`) to keep
     * temp table memory/disk strictly O(chunkSize) for 10M+ row feeds, and guards updates with
     * `IS DISTINCT FROM` (`$skipUnchanged`) to avoid MVCC dead-tuple bloat.
     *
     * On SQLite/MySQL: automatically falls back to chunked parameterized multi-row upsert inside a transaction
     * with matching last-row-wins batch deduplication when `$deduplicate` is true.
     *
     * @param list<string>                        $columns
     * @param list<string>                        $conflictColumns
     * @param iterable<int, array<string, mixed>> $rows
     * @param ?list<string>                       $updateColumns Null updates all non-conflict columns; `[]` performs `DO NOTHING`
     */
    public function upsert(
        string $table,
        array $columns,
        array $conflictColumns,
        iterable $rows,
        ?array $updateColumns = null,
        int $chunkSize = 10_000,
        bool $skipUnchanged = true,
        bool $deduplicate = true,
        bool $mergePerChunk = false,
    ): BulkUpsertResult {
        $this->assertValidTable($table);
        $this->assertValidColumns($columns);
        $this->assertValidColumns($conflictColumns, 'Conflict column');
        foreach ($conflictColumns as $cc) {
            if (!in_array($cc, $columns, true)) {
                throw new DatabaseException(sprintf('Conflict column "%s" must be included in $columns.', $cc));
            }
        }
        if ($chunkSize < 1) {
            throw new DatabaseException('Chunk size must be at least 1.');
        }

        if (null === $updateColumns) {
            $resolvedUpdateCols = array_values(array_diff($columns, $conflictColumns));
        } else {
            foreach ($updateColumns as $uc) {
                $this->assertValidIdent($uc, 'Update column');
                if (!in_array($uc, $columns, true)) {
                    throw new DatabaseException(sprintf('Update column "%s" must be included in $columns.', $uc));
                }
            }
            $resolvedUpdateCols = $updateColumns;
        }

        $startNs = hrtime(true);
        $usePgCopy = $this->canUsePgCopy();
        $dialect = $this->connection->dialect();
        $effectiveChunkSize = $usePgCopy ? $chunkSize : $this->clampParameterizedChunkSize($dialect, count($columns), $chunkSize);

        $rowsProcessed = 0;
        $rowsAffected = 0;
        $chunks = 0;

        $this->connection->transaction(function () use (
            $table,
            $columns,
            $conflictColumns,
            $resolvedUpdateCols,
            $rows,
            $effectiveChunkSize,
            $usePgCopy,
            $skipUnchanged,
            $deduplicate,
            $mergePerChunk,
            &$rowsProcessed,
            &$rowsAffected,
            &$chunks,
        ): void {
            if ($usePgCopy && $this->connection instanceof PdoConnection) {
                $stageTable = 'kgn_stage_' . bin2hex(random_bytes(6));
                $quotedStage = self::quoteIdent($stageTable, Dialect::Postgres);
                $quotedFields = implode(', ', array_map(
                    static fn(string $col): string => self::quoteIdent($col, Dialect::Postgres),
                    $columns,
                ));

                $this->connection->execute(self::buildPostgresStageTableSql($table, $stageTable, $columns));
                $mergeSql = self::buildPostgresMergeSql(
                    $table,
                    $stageTable,
                    $columns,
                    $conflictColumns,
                    $resolvedUpdateCols,
                    $skipUnchanged,
                    $deduplicate,
                );

                /** @var list<string> $lines */
                $lines = [];
                foreach ($rows as $row) {
                    $lines[] = self::encodeCopyLine($row, $columns);
                    $rowsProcessed++;
                    if (count($lines) >= $effectiveChunkSize) {
                        $chunks++;
                        $this->connection->copyFromArray($quotedStage, $lines, "\t", '\\\\N', $quotedFields);
                        $lines = [];
                        if ($mergePerChunk) {
                            $rowsAffected += $this->connection->execute($mergeSql);
                            $this->connection->execute('TRUNCATE ' . $quotedStage);
                        }
                    }
                }
                if ([] !== $lines) {
                    $chunks++;
                    $this->connection->copyFromArray($quotedStage, $lines, "\t", '\\\\N', $quotedFields);
                    if ($mergePerChunk) {
                        $rowsAffected += $this->connection->execute($mergeSql);
                        $this->connection->execute('TRUNCATE ' . $quotedStage);
                    }
                }

                if (!$mergePerChunk && $rowsProcessed > 0) {
                    $rowsAffected = $this->connection->execute($mergeSql);
                }

                return;
            }

            /** @var list<array<string, mixed>> $buffer */
            $buffer = [];
            foreach ($rows as $row) {
                $buffer[] = $row;
                $rowsProcessed++;
                if (count($buffer) >= $effectiveChunkSize) {
                    $chunks++;
                    $effectiveBatch = $deduplicate ? self::deduplicateBatch($buffer, $conflictColumns) : $buffer;
                    $rowsAffected += $this->executeMultiRowUpsert($table, $columns, $conflictColumns, $resolvedUpdateCols, $effectiveBatch, $skipUnchanged);
                    $buffer = [];
                }
            }
            if ([] !== $buffer) {
                $chunks++;
                $effectiveBatch = $deduplicate ? self::deduplicateBatch($buffer, $conflictColumns) : $buffer;
                $rowsAffected += $this->executeMultiRowUpsert($table, $columns, $conflictColumns, $resolvedUpdateCols, $effectiveBatch, $skipUnchanged);
            }
        });

        return new BulkUpsertResult(
            rowsProcessed: $rowsProcessed,
            rowsAffected: $rowsAffected,
            chunks: $chunks,
            strategy: $usePgCopy ? 'pg_copy' : 'chunked_insert',
            durationMs: round((hrtime(true) - $startNs) / 1_000_000, 3),
        );
    }

    /**
     * Build the narrow `CREATE TEMP TABLE ... ON COMMIT DROP AS SELECT ... WHERE false` staging DDL.
     * Avoids `LIKE` inheriting `NOT NULL` constraints or generated columns on omitted target columns.
     *
     * @param list<string> $columns
     */
    public static function buildPostgresStageTableSql(string $table, string $stageTable, array $columns): string
    {
        $qTarget = self::quoteTable($table, Dialect::Postgres);
        $qStage = self::quoteIdent($stageTable, Dialect::Postgres);
        $colList = implode(', ', array_map(static fn(string $c): string => self::quoteIdent($c, Dialect::Postgres), $columns));

        return sprintf(
            'CREATE TEMP TABLE %s ON COMMIT DROP AS SELECT %s FROM %s WHERE false',
            $qStage,
            $colList,
            $qTarget,
        );
    }

    /**
     * Build the exact PostgreSQL `INSERT ... SELECT FROM stage ON CONFLICT` merge statement.
     * When `$deduplicate` is true on `DO UPDATE`, uses `SELECT DISTINCT ON (...) ... ORDER BY ..., ctid DESC`
     * (last-row-wins) to prevent PostgreSQL SQLSTATE 21000 on duplicate keys within a feed batch.
     *
     * @param list<string> $columns
     * @param list<string> $conflictColumns
     * @param list<string> $updateColumns
     */
    public static function buildPostgresMergeSql(
        string $table,
        string $stageTable,
        array $columns,
        array $conflictColumns,
        array $updateColumns,
        bool $skipUnchanged = true,
        bool $deduplicate = true,
    ): string {
        $qTarget = self::quoteTable($table, Dialect::Postgres);
        $qStage = self::quoteIdent($stageTable, Dialect::Postgres);
        $colList = implode(', ', array_map(static fn(string $c): string => self::quoteIdent($c, Dialect::Postgres), $columns));
        $conflictList = implode(', ', array_map(static fn(string $c): string => self::quoteIdent($c, Dialect::Postgres), $conflictColumns));

        if ([] === $updateColumns) {
            return sprintf(
                'INSERT INTO %s (%s) SELECT %s FROM %s ON CONFLICT (%s) DO NOTHING',
                $qTarget,
                $colList,
                $colList,
                $qStage,
                $conflictList,
            );
        }

        $selectClause = $deduplicate
            ? sprintf('SELECT DISTINCT ON (%s) %s FROM %s ORDER BY %s, ctid DESC', $conflictList, $colList, $qStage, $conflictList)
            : sprintf('SELECT %s FROM %s', $colList, $qStage);

        $sets = [];
        $distinctChecks = [];
        foreach ($updateColumns as $col) {
            $qc = self::quoteIdent($col, Dialect::Postgres);
            $sets[] = sprintf('%s = EXCLUDED.%s', $qc, $qc);
            $distinctChecks[] = sprintf('%s.%s IS DISTINCT FROM EXCLUDED.%s', $qTarget, $qc, $qc);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) %s ON CONFLICT (%s) DO UPDATE SET %s',
            $qTarget,
            $colList,
            $selectClause,
            $conflictList,
            implode(', ', $sets),
        );

        if ($skipUnchanged) {
            $sql .= ' WHERE (' . implode(' OR ', $distinctChecks) . ')';
        }

        return $sql;
    }

    /**
     * Last-row-wins in-batch deduplication by conflict key tuple (mirrors Postgres `DISTINCT ON ... ORDER BY ..., ctid DESC`).
     *
     * @param list<array<string, mixed>> $batch
     * @param list<string>               $conflictColumns
     * @return list<array<string, mixed>>
     */
    private static function deduplicateBatch(array $batch, array $conflictColumns): array
    {
        /** @var array<string, array<string, mixed>> $deduped */
        $deduped = [];
        foreach ($batch as $row) {
            $keyParts = [];
            foreach ($conflictColumns as $col) {
                $keyParts[] = $row[$col] ?? null;
            }
            try {
                $compositeKey = json_encode($keyParts, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new DatabaseException('Unable to encode conflict key tuple for batch deduplication: ' . $e->getMessage(), previous: $e);
            }
            $deduped[$compositeKey] = $row;
        }

        return array_values($deduped);
    }

    /**
     * Encode a single associative row into a strict PostgreSQL `COPY` text-format line (tab-delimited).
     *
     * @param array<string, mixed> $row
     * @param list<string>         $columns
     */
    public static function encodeCopyLine(array $row, array $columns): string
    {
        $parts = [];
        foreach ($columns as $col) {
            $parts[] = self::encodeCopyValue($row[$col] ?? null);
        }

        return implode("\t", $parts);
    }

    /** Encode one scalar/JSON/null value safely for PostgreSQL `COPY` text format (`\t` delimiter, `\N` NULL). */
    public static function encodeCopyValue(mixed $value): string
    {
        if (null === $value) {
            return '\N';
        }
        if (is_bool($value)) {
            return $value ? 't' : 'f';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            try {
                $raw = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
                throw new DatabaseException('Failed JSON-encoding array value for PostgreSQL COPY: ' . $e->getMessage(), previous: $e);
            }
        } elseif (is_string($value)) {
            $raw = $value;
        } elseif ($value instanceof Stringable) {
            $raw = (string) $value;
        } else {
            throw new DatabaseException(sprintf('Unsupported value type "%s" for PostgreSQL COPY.', get_debug_type($value)));
        }

        // PostgreSQL UTF-8 text columns reject raw NUL bytes; strip NUL and escape COPY metacharacters.
        $clean = str_replace("\0", '', $raw);

        return strtr($clean, [
            '\\' => '\\\\',
            "\t" => '\t',
            "\n" => '\n',
            "\r" => '\r',
        ]);
    }

    private function canUsePgCopy(): bool
    {
        return $this->connection instanceof PdoConnection
            && Dialect::Postgres === $this->connection->dialect();
    }

    private function clampParameterizedChunkSize(Dialect $dialect, int $columnCount, int $requested): int
    {
        $maxVars = Dialect::Sqlite === $dialect ? self::SQLITE_MAX_VARS : self::MYSQL_MAX_VARS;
        $maxRows = max(1, intdiv($maxVars, max(1, $columnCount)));

        return min($requested, $maxRows);
    }

    /**
     * @param list<string>               $columns
     * @param list<array<string, mixed>> $batch
     */
    private function executeMultiRowInsert(string $table, array $columns, array $batch): int
    {
        $dialect = $this->connection->dialect();
        $qTable = self::quoteTable($table, $dialect);
        $colList = implode(', ', array_map(static fn(string $c): string => self::quoteIdent($c, $dialect), $columns));
        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $placeholders = [];
        $bindings = [];
        foreach ($batch as $row) {
            $placeholders[] = $rowPlaceholder;
            foreach ($columns as $col) {
                $bindings[] = self::normalizeBinding($row[$col] ?? null);
            }
        }

        $sql = sprintf('INSERT INTO %s (%s) VALUES %s', $qTable, $colList, implode(', ', $placeholders));

        return $this->connection->execute($sql, $bindings);
    }

    /**
     * @param list<string>               $columns
     * @param list<string>               $conflictColumns
     * @param list<string>               $updateColumns
     * @param list<array<string, mixed>> $batch
     */
    private function executeMultiRowUpsert(
        string $table,
        array $columns,
        array $conflictColumns,
        array $updateColumns,
        array $batch,
        bool $skipUnchanged,
    ): int {
        $dialect = $this->connection->dialect();
        $qTable = self::quoteTable($table, $dialect);
        $colList = implode(', ', array_map(static fn(string $c): string => self::quoteIdent($c, $dialect), $columns));
        $rowPlaceholder = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        $placeholders = [];
        $bindings = [];
        foreach ($batch as $row) {
            $placeholders[] = $rowPlaceholder;
            foreach ($columns as $col) {
                $bindings[] = self::normalizeBinding($row[$col] ?? null);
            }
        }

        $baseSql = sprintf('INSERT INTO %s (%s) VALUES %s', $qTable, $colList, implode(', ', $placeholders));

        if (Dialect::Mysql === $dialect) {
            if ([] === $updateColumns) {
                $sql = sprintf('INSERT IGNORE INTO %s (%s) VALUES %s', $qTable, $colList, implode(', ', $placeholders));
            } else {
                $sets = array_map(static function (string $col) use ($dialect): string {
                    $qc = self::quoteIdent($col, $dialect);

                    return sprintf('%s = VALUES(%s)', $qc, $qc);
                }, $updateColumns);
                $sql = $baseSql . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
            }

            return $this->connection->execute($sql, $bindings);
        }

        $conflictList = implode(', ', array_map(static fn(string $c): string => self::quoteIdent($c, $dialect), $conflictColumns));
        if ([] === $updateColumns) {
            $sql = sprintf('%s ON CONFLICT (%s) DO NOTHING', $baseSql, $conflictList);

            return $this->connection->execute($sql, $bindings);
        }

        $sets = [];
        $distinctChecks = [];
        $distinctOp = Dialect::Sqlite === $dialect ? 'IS NOT' : 'IS DISTINCT FROM';
        foreach ($updateColumns as $col) {
            $qc = self::quoteIdent($col, $dialect);
            $sets[] = sprintf('%s = excluded.%s', $qc, $qc);
            $distinctChecks[] = sprintf('%s.%s %s excluded.%s', $qTable, $qc, $distinctOp, $qc);
        }

        $sql = sprintf('%s ON CONFLICT (%s) DO UPDATE SET %s', $baseSql, $conflictList, implode(', ', $sets));
        if ($skipUnchanged) {
            $sql .= ' WHERE (' . implode(' OR ', $distinctChecks) . ')';
        }

        return $this->connection->execute($sql, $bindings);
    }

    private static function normalizeBinding(mixed $value): mixed
    {
        if (is_array($value)) {
            try {
                return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $e) {
                throw new DatabaseException('Failed JSON-encoding array value for bulk insert: ' . $e->getMessage(), previous: $e);
            }
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return $value;
    }

    private function assertValidTable(string $table): void
    {
        $parts = explode('.', $table);
        if (count($parts) > 2) {
            throw new DatabaseException(sprintf('Unsafe table identifier "%s".', $table));
        }
        foreach ($parts as $part) {
            $this->assertValidIdent($part, 'Table');
        }
    }

    /** @param list<string> $columns */
    private function assertValidColumns(array $columns, string $label = 'Column'): void
    {
        if ([] === $columns) {
            throw new DatabaseException(sprintf('%s list cannot be empty.', $label));
        }
        foreach ($columns as $col) {
            $this->assertValidIdent($col, $label);
        }
    }

    private function assertValidIdent(string $ident, string $label): void
    {
        if (1 !== preg_match(self::IDENT_PATTERN, $ident)) {
            throw new DatabaseException(sprintf('Unsafe %s identifier "%s".', $label, $ident));
        }
    }

    private static function quoteTable(string $table, Dialect $dialect): string
    {
        return implode('.', array_map(
            static fn(string $part): string => self::quoteIdent($part, $dialect),
            explode('.', $table),
        ));
    }

    private static function quoteIdent(string $ident, Dialect $dialect): string
    {
        return Dialect::Mysql === $dialect
            ? '`' . $ident . '`'
            : '"' . $ident . '"';
    }
}
