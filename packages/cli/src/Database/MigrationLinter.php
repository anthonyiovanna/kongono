<?php

declare(strict_types=1);

namespace Kongono\Cli\Database;

/**
 * Static zero-downtime migration linter for Kongono applications.
 *
 * Inspects `migrations/*.php` before production deployments to catch table-locking DDL,
 * missing CONCURRENTLY indexes, rolling-deploy DROP hazards, and unscoped destructive statements.
 */
final readonly class MigrationLinter
{
    /**
     * @return array{
     *     passed: bool,
     *     files_scanned: int,
     *     counts: array{critical: int, warning: int},
     *     findings: list<array{severity: 'critical'|'warning', code: string, file: string, message: string}>
     * }
     */
    public function lintDirectory(string $migrationsDir): array
    {
        /** @var list<array{severity: 'critical'|'warning', code: string, file: string, message: string}> $findings */
        $findings = [];
        if (!is_dir($migrationsDir)) {
            return [
                'passed' => true,
                'files_scanned' => 0,
                'counts' => ['critical' => 0, 'warning' => 0],
                'findings' => [],
            ];
        }

        $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
        if (false === $files) {
            $files = [];
        }
        sort($files, SORT_STRING);

        foreach ($files as $path) {
            $contents = file_get_contents($path);
            if (false === $contents) {
                continue;
            }
            $fileName = basename($path);
            $this->lintSource($fileName, $contents, $findings);
        }

        $counts = ['critical' => 0, 'warning' => 0];
        foreach ($findings as $finding) {
            ++$counts[$finding['severity']];
        }

        return [
            'passed' => 0 === $counts['critical'],
            'files_scanned' => count($files),
            'counts' => $counts,
            'findings' => $findings,
        ];
    }

    /**
     * Lint only the forward `up()` portion (or full file if no `down()` method split exists)
     * so rollback helpers in `down()` do not trigger false positives for DROP TABLE.
     *
     * @param list<array{severity: 'critical'|'warning', code: string, file: string, message: string}> $findings
     */
    private function lintSource(string $fileName, string $source, array &$findings): void
    {
        $upSource = $source;
        $downPos = stripos($source, 'function down(');
        if (false !== $downPos) {
            $upSource = substr($source, 0, $downPos);
        }

        // 1. ADD COLUMN ... NOT NULL without DEFAULT (locks table + fails on non-empty tables)
        if (1 === preg_match('/\bALTER\s+TABLE\b[^;]*\bADD\s+(?:COLUMN\s+)?[^;]*\bNOT\s+NULL\b/i', $upSource, $m)
            && 1 !== preg_match('/\bDEFAULT\b/i', $m[0])
        ) {
            $findings[] = [
                'severity' => 'critical',
                'code' => 'KGN-MIG-NOT-NULL-NO-DEFAULT',
                'file' => $fileName,
                'message' => 'ALTER TABLE ADD COLUMN ... NOT NULL without DEFAULT fails on non-empty tables and locks writes. Add a DEFAULT or backfill first.',
            ];
        }

        // 2. TRUNCATE or unscoped DELETE FROM inside forward migration
        if (1 === preg_match('/\bTRUNCATE\s+TABLE\b/i', $upSource)) {
            $findings[] = [
                'severity' => 'critical',
                'code' => 'KGN-MIG-TRUNCATE',
                'file' => $fileName,
                'message' => 'TRUNCATE TABLE detected in forward migration — irreversible data loss and exclusive table lock.',
            ];
        }

        if (1 === preg_match('/\bDELETE\s+FROM\s+[a-zA-Z0-9_."`]+\s*(?:;|\'|")/i', $upSource)) {
            $findings[] = [
                'severity' => 'critical',
                'code' => 'KGN-MIG-UNSCOPED-DELETE',
                'file' => $fileName,
                'message' => 'Unscoped DELETE FROM without WHERE detected in forward migration.',
            ];
        }

        // 3. CREATE INDEX without CONCURRENTLY (blocks writes on large Postgres tables)
        if (1 === preg_match('/\bCREATE\s+(?:UNIQUE\s+)?INDEX\s+(?!CONCURRENTLY\b)/i', $upSource)) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'KGN-MIG-LOCKING-INDEX',
                'file' => $fileName,
                'message' => 'CREATE INDEX without CONCURRENTLY blocks writes in PostgreSQL. Prefer CREATE INDEX CONCURRENTLY for live tables.',
            ];
        }

        // 4. DROP COLUMN or DROP TABLE in forward migration (rolling-deploy hazard)
        if (1 === preg_match('/\bDROP\s+COLUMN\b/i', $upSource)) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'KGN-MIG-DROP-COLUMN',
                'file' => $fileName,
                'message' => 'DROP COLUMN in forward migration can break running workers during zero-downtime rolling deploys. Stop reading the column in an earlier release first.',
            ];
        }

        if (1 === preg_match('/\bDROP\s+TABLE\b/i', $upSource)) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'KGN-MIG-DROP-TABLE',
                'file' => $fileName,
                'message' => 'DROP TABLE detected in forward migration — ensure all application references were removed in an earlier deploy.',
            ];
        }
    }
}
