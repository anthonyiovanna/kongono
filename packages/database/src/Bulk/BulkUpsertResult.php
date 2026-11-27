<?php

declare(strict_types=1);

namespace Kongono\Database\Bulk;

/** Summary telemetry returned by `PgCopyWriter::insert()` and `PgCopyWriter::upsert()`. */
final readonly class BulkUpsertResult
{
    public function __construct(
        public int $rowsProcessed,
        public int $rowsAffected,
        public int $chunks,
        public string $strategy,
        public float $durationMs,
    ) {}
}
