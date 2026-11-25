<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use JsonException;
use Kongono\Database\ConnectionInterface;

/**
 * Persistent PostgreSQL queue driver using short `FOR UPDATE SKIP LOCKED`
 * reservation transactions (never held while handlers execute), generation/token fencing,
 * `uniqueKey` deduplication, and dead-letter replay.
 */
final readonly class PostgresQueueDriver implements QueueDriverInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'kongono_jobs',
    ) {}

    public function installSchema(): void
    {
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $this->db->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS %s ('
            . 'id VARCHAR(64) PRIMARY KEY, '
            . 'queue VARCHAR(128) NOT NULL, '
            . 'type VARCHAR(192) NOT NULL, '
            . 'payload TEXT NOT NULL, '
            . 'attempt INTEGER NOT NULL DEFAULT 0, '
            . 'max_attempts INTEGER NOT NULL DEFAULT 3, '
            . 'generation INTEGER NOT NULL DEFAULT 0, '
            . 'lease_token VARCHAR(64), '
            . 'available_at BIGINT NOT NULL, '
            . 'lease_until BIGINT, '
            . 'status VARCHAR(32) NOT NULL, '
            . 'last_error TEXT, '
            . 'unique_key VARCHAR(192), '
            . 'updated_at BIGINT NOT NULL'
            . ')',
            $t,
        ));

        // Add polling index and partial unique index for atomic uniqueKey deduplication
        $safeTableName = str_replace('.', '_', $this->table);
        $pollIdx = $this->db->dialect()->quoteIdentifier('idx_' . $safeTableName . '_poll');
        $this->db->execute(sprintf(
            'CREATE INDEX IF NOT EXISTS %s ON %s (queue, status, available_at)',
            $pollIdx,
            $t,
        ));
        if (\Kongono\Database\Dialect::Mysql !== $this->db->dialect()) {
            $uniqIdx = $this->db->dialect()->quoteIdentifier('idx_' . $safeTableName . '_unique_active');
            $this->db->execute(sprintf(
                "CREATE UNIQUE INDEX IF NOT EXISTS %s ON %s (queue, unique_key) WHERE unique_key IS NOT NULL AND status IN ('pending', 'reserved')",
                $uniqIdx,
                $t,
            ));
        }
    }

    public function push(
        string $queue,
        string $type,
        array $payload = [],
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?int $now = null,
        ?string $uniqueKey = null,
    ): Job {
        $ts = $now ?? time();
        $cleanUnique = null !== $uniqueKey && '' !== trim($uniqueKey) ? trim($uniqueKey) : null;
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES, 64);

        if (null !== $cleanUnique) {
            $t = $this->db->dialect()->quoteIdentifier($this->table);
            $existing = $this->db->first(
                sprintf('SELECT * FROM %s WHERE queue = ? AND unique_key = ? AND status IN (?, ?) LIMIT 1', $t),
                [$queue, $cleanUnique, JobStatus::Pending->value, JobStatus::Reserved->value],
            );
            if (null !== $existing) {
                return $this->hydrateRow($existing);
            }
        }

        $id = bin2hex(random_bytes(16));
        $availableAt = $ts + max(0, $delaySeconds);
        $attempts = max(1, $maxAttempts);

        $row = [
            'id' => $id,
            'queue' => $queue,
            'type' => $type,
            'payload' => $encoded,
            'attempt' => 0,
            'max_attempts' => $attempts,
            'generation' => 0,
            'lease_token' => null,
            'available_at' => $availableAt,
            'lease_until' => null,
            'status' => JobStatus::Pending->value,
            'last_error' => null,
            'updated_at' => $ts,
        ];
        if (null !== $cleanUnique) {
            $row['unique_key'] = $cleanUnique;
        }

        // Use ON CONFLICT ... DO NOTHING on Postgres/SQLite so a concurrent uniqueKey collision never aborts an enclosing PG transaction (SQLSTATE 25P02)
        if (null !== $cleanUnique && \Kongono\Database\Dialect::Mysql !== $this->db->dialect()) {
            $insert = $this->db->table($this->table)->toInsertSql($row);
            $inserted = $this->db->execute(
                $insert->query . " ON CONFLICT (queue, unique_key) WHERE unique_key IS NOT NULL AND status IN ('pending', 'reserved') DO NOTHING",
                $insert->bindings,
            );
            if (0 === $inserted) {
                $t = $this->db->dialect()->quoteIdentifier($this->table);
                $existing = $this->db->first(
                    sprintf('SELECT * FROM %s WHERE queue = ? AND unique_key = ? AND status IN (?, ?) LIMIT 1', $t),
                    [$queue, $cleanUnique, JobStatus::Pending->value, JobStatus::Reserved->value],
                );
                if (null !== $existing) {
                    return $this->hydrateRow($existing);
                }
            }
        } else {
            try {
                $this->db->table($this->table)->insert($row);
            } catch (\Throwable $e) {
                if (null !== $cleanUnique) {
                    $t = $this->db->dialect()->quoteIdentifier($this->table);
                    $existing = $this->db->first(
                        sprintf('SELECT * FROM %s WHERE queue = ? AND unique_key = ? AND status IN (?, ?) LIMIT 1', $t),
                        [$queue, $cleanUnique, JobStatus::Pending->value, JobStatus::Reserved->value],
                    );
                    if (null !== $existing) {
                        return $this->hydrateRow($existing);
                    }
                }
                throw $e;
            }
        }

        return new Job($id, $queue, $type, $payload, 0, $attempts, 0, null, $availableAt, null, JobStatus::Pending, null, $cleanUnique);
    }

    public function reserve(string $queue, int $leaseSeconds = 60, ?int $now = null): ?Job
    {
        $ts = $now ?? time();
        $leaseUntil = $ts + max(1, $leaseSeconds);
        $token = bin2hex(random_bytes(16));
        $t = $this->db->dialect()->quoteIdentifier($this->table);

        return $this->db->transaction(function (ConnectionInterface $tx) use ($t, $queue, $ts, $leaseUntil, $token): ?Job {
            for ($scan = 0; $scan < 32; ++$scan) {
                $row = $tx->first(
                    sprintf(
                        'SELECT * FROM %s WHERE queue = ? AND ((status = ? AND available_at <= ?) OR (status = ? AND lease_until <= ?)) ORDER BY available_at ASC LIMIT 1 FOR UPDATE SKIP LOCKED',
                        $t,
                    ),
                    [$queue, JobStatus::Pending->value, $ts, JobStatus::Reserved->value, $ts],
                );

                if (null === $row) {
                    return null;
                }

                $rowId = is_string($row['id'] ?? null) ? $row['id'] : '';
                try {
                    $job = $this->hydrateRow($row);
                } catch (JobException $e) {
                    // Dead-letter corrupt/poison-pill payload rows so they can NEVER halt head-of-line queue progress
                    if ('' !== $rowId) {
                        $tx->execute(
                            sprintf('UPDATE %s SET status = ?, generation = generation + 1, lease_token = NULL, lease_until = NULL, last_error = ?, updated_at = ? WHERE id = ?', $t),
                            [JobStatus::Dead->value, $e->getMessage(), $ts, $rowId],
                        );
                    }
                    continue;
                }

                // Transition expired final-attempt leases to Dead instead of leaving permanent zombie reserved rows
                if ($job->attempt >= $job->maxAttempts) {
                    $tx->execute(
                        sprintf('UPDATE %s SET status = ?, generation = ?, lease_token = NULL, lease_until = NULL, last_error = ?, updated_at = ? WHERE id = ? AND generation = ?', $t),
                        [
                            JobStatus::Dead->value,
                            $job->generation + 1,
                            $job->lastError ?? 'Maximum attempts exhausted after lease expiration.',
                            $ts,
                            $job->id,
                            $job->generation,
                        ],
                    );
                    continue;
                }

                $newAttempt = $job->attempt + 1;
                $newGeneration = $job->generation + 1;

                $updated = $tx->execute(
                    sprintf(
                        'UPDATE %s SET status = ?, attempt = ?, generation = ?, lease_token = ?, lease_until = ?, updated_at = ? WHERE id = ? AND generation = ?',
                        $t,
                    ),
                    [JobStatus::Reserved->value, $newAttempt, $newGeneration, $token, $leaseUntil, $ts, $job->id, $job->generation],
                );

                if (1 !== $updated) {
                    return null;
                }

                return new Job(
                    id: $job->id,
                    queue: $job->queue,
                    type: $job->type,
                    payload: $job->payload,
                    attempt: $newAttempt,
                    maxAttempts: $job->maxAttempts,
                    generation: $newGeneration,
                    leaseToken: $token,
                    availableAt: $job->availableAt,
                    leaseUntil: $leaseUntil,
                    status: JobStatus::Reserved,
                    lastError: $job->lastError,
                    uniqueKey: $job->uniqueKey,
                );
            }

            return null;
        });
    }

    public function acknowledge(Job $job): bool
    {
        if (null === $job->leaseToken) {
            return false;
        }
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $updated = $this->db->execute(
            sprintf(
                'UPDATE %s SET status = ?, lease_token = NULL, lease_until = NULL, last_error = NULL, updated_at = ? WHERE id = ? AND status = ? AND generation = ? AND lease_token = ?',
                $t,
            ),
            [JobStatus::Completed->value, time(), $job->id, JobStatus::Reserved->value, $job->generation, $job->leaseToken],
        );

        return 1 === $updated;
    }

    public function releaseForRetry(Job $job, int $delaySeconds, ?string $error = null, ?int $now = null): bool
    {
        if (null === $job->leaseToken) {
            return false;
        }
        $ts = $now ?? time();
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $updated = $this->db->execute(
            sprintf(
                'UPDATE %s SET status = ?, available_at = ?, lease_token = NULL, lease_until = NULL, last_error = ?, updated_at = ? WHERE id = ? AND status = ? AND generation = ? AND lease_token = ?',
                $t,
            ),
            [JobStatus::Pending->value, $ts + max(0, $delaySeconds), $error, $ts, $job->id, JobStatus::Reserved->value, $job->generation, $job->leaseToken],
        );

        return 1 === $updated;
    }

    public function markFailed(Job $job, JobStatus $terminalStatus, ?string $error = null): bool
    {
        if (null === $job->leaseToken) {
            return false;
        }
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $updated = $this->db->execute(
            sprintf(
                'UPDATE %s SET status = ?, lease_token = NULL, lease_until = NULL, last_error = ?, updated_at = ? WHERE id = ? AND status = ? AND generation = ? AND lease_token = ?',
                $t,
            ),
            [$terminalStatus->value, $error, time(), $job->id, JobStatus::Reserved->value, $job->generation, $job->leaseToken],
        );

        return 1 === $updated;
    }

    public function stats(string $queue): array
    {
        $counts = ['pending' => 0, 'reserved' => 0, 'completed' => 0, 'failed' => 0, 'dead' => 0];
        $rows = $this->db->table($this->table)
            ->select('status', 'COUNT(*) AS total')
            ->where('queue', '=', $queue)
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $status = is_string($row['status'] ?? null) ? $row['status'] : '';
            $total = is_numeric($row['total'] ?? null) ? (int) $row['total'] : 0;
            if (array_key_exists($status, $counts)) {
                $counts[$status] = $total;
            }
        }

        return $counts;
    }

    public function prune(string $queue, int $olderThanTimestamp): int
    {
        return $this->db->table($this->table)
            ->where('queue', '=', $queue)
            ->whereIn('status', [JobStatus::Completed->value, JobStatus::Failed->value, JobStatus::Dead->value])
            ->where('updated_at', '<=', $olderThanTimestamp)
            ->delete();
    }

    public function listFailed(string $queue, int $limit = 100): array
    {
        $rows = $this->db->table($this->table)
            ->where('queue', '=', $queue)
            ->whereIn('status', [JobStatus::Failed->value, JobStatus::Dead->value])
            ->orderBy('updated_at', 'DESC')
            ->limit(max(1, $limit))
            ->get();

        $jobs = [];
        foreach ($rows as $row) {
            try {
                $jobs[] = $this->hydrateRow($row);
            } catch (JobException) {
                continue;
            }
        }

        return $jobs;
    }

    public function retryFailed(string $queue, ?string $jobId = null, ?int $now = null): int
    {
        $ts = $now ?? time();
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $hasPartialUnique = \Kongono\Database\Dialect::Mysql !== $this->db->dialect();

        if (null !== $jobId) {
            if ($hasPartialUnique) {
                return $this->db->execute(
                    sprintf(
                        'UPDATE %s SET status = ?, attempt = 0, generation = generation + 1, lease_token = NULL, lease_until = NULL, available_at = ?, last_error = NULL, updated_at = ? '
                        . 'WHERE queue = ? AND id = ? AND status IN (?, ?) '
                        . 'AND (unique_key IS NULL OR NOT EXISTS (SELECT 1 FROM %s active WHERE active.queue = %s.queue AND active.unique_key = %s.unique_key AND active.status IN (?, ?)))',
                        $t,
                        $t,
                        $t,
                        $t,
                    ),
                    [
                        JobStatus::Pending->value,
                        $ts,
                        $ts,
                        $queue,
                        $jobId,
                        JobStatus::Failed->value,
                        JobStatus::Dead->value,
                        JobStatus::Pending->value,
                        JobStatus::Reserved->value,
                    ],
                );
            }

            return $this->db->execute(
                sprintf(
                    'UPDATE %s SET status = ?, attempt = 0, generation = generation + 1, lease_token = NULL, lease_until = NULL, available_at = ?, last_error = NULL, updated_at = ? '
                    . 'WHERE queue = ? AND id = ? AND status IN (?, ?) '
                    . 'AND (unique_key IS NULL OR unique_key NOT IN (SELECT uk FROM (SELECT unique_key AS uk FROM %s WHERE queue = ? AND unique_key IS NOT NULL AND status IN (?, ?)) AS active_keys))',
                    $t,
                    $t,
                ),
                [
                    JobStatus::Pending->value,
                    $ts,
                    $ts,
                    $queue,
                    $jobId,
                    JobStatus::Failed->value,
                    JobStatus::Dead->value,
                    $queue,
                    JobStatus::Pending->value,
                    JobStatus::Reserved->value,
                ],
            );
        }

        if ($hasPartialUnique) {
            return $this->db->execute(
                sprintf(
                    'UPDATE %s SET status = ?, attempt = 0, generation = generation + 1, lease_token = NULL, lease_until = NULL, available_at = ?, last_error = NULL, updated_at = ? '
                    . 'WHERE queue = ? AND status IN (?, ?) '
                    . 'AND (unique_key IS NULL OR ('
                    . 'NOT EXISTS (SELECT 1 FROM %s active WHERE active.queue = %s.queue AND active.unique_key = %s.unique_key AND active.status IN (?, ?)) '
                    . 'AND id = (SELECT f.id FROM %s f WHERE f.queue = %s.queue AND f.unique_key = %s.unique_key AND f.status IN (?, ?) ORDER BY f.updated_at DESC, f.id DESC LIMIT 1)'
                    . '))',
                    $t,
                    $t,
                    $t,
                    $t,
                    $t,
                    $t,
                    $t,
                ),
                [
                    JobStatus::Pending->value,
                    $ts,
                    $ts,
                    $queue,
                    JobStatus::Failed->value,
                    JobStatus::Dead->value,
                    JobStatus::Pending->value,
                    JobStatus::Reserved->value,
                    JobStatus::Failed->value,
                    JobStatus::Dead->value,
                ],
            );
        }

        return $this->db->execute(
            sprintf(
                'UPDATE %s SET status = ?, attempt = 0, generation = generation + 1, lease_token = NULL, lease_until = NULL, available_at = ?, last_error = NULL, updated_at = ? '
                . 'WHERE queue = ? AND status IN (?, ?) '
                . 'AND (unique_key IS NULL OR ('
                . 'unique_key NOT IN (SELECT uk FROM (SELECT unique_key AS uk FROM %s WHERE queue = ? AND unique_key IS NOT NULL AND status IN (?, ?)) AS active_keys) '
                . 'AND id IN (SELECT keep_id FROM (SELECT id AS keep_id FROM (SELECT id, ROW_NUMBER() OVER (PARTITION BY unique_key ORDER BY updated_at DESC, id DESC) AS rn FROM %s WHERE queue = ? AND unique_key IS NOT NULL AND status IN (?, ?)) AS ranked WHERE rn = 1) AS latest_failed)'
                . '))',
                $t,
                $t,
                $t,
            ),
            [
                JobStatus::Pending->value,
                $ts,
                $ts,
                $queue,
                JobStatus::Failed->value,
                JobStatus::Dead->value,
                $queue,
                JobStatus::Pending->value,
                JobStatus::Reserved->value,
                $queue,
                JobStatus::Failed->value,
                JobStatus::Dead->value,
            ],
        );
    }

    public function flushFailed(string $queue): int
    {
        return $this->db->table($this->table)
            ->where('queue', '=', $queue)
            ->whereIn('status', [JobStatus::Failed->value, JobStatus::Dead->value])
            ->delete();
    }

    /** @param array<string, mixed> $row */
    private function hydrateRow(array $row): Job
    {
        $rawPayload = is_string($row['payload'] ?? null) ? $row['payload'] : '{}';
        try {
            $decoded = json_decode($rawPayload, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new JobException('Stored job payload is not valid JSON.', previous: $e);
        }

        /** @var array<string, mixed> $payload */
        $payload = [];
        if (is_array($decoded)) {
            foreach ($decoded as $k => $v) {
                $payload[(string) $k] = $v;
            }
        }

        return new Job(
            id: is_string($row['id'] ?? null) ? $row['id'] : '',
            queue: is_string($row['queue'] ?? null) ? $row['queue'] : 'default',
            type: is_string($row['type'] ?? null) ? $row['type'] : '',
            payload: $payload,
            attempt: is_numeric($row['attempt'] ?? null) ? (int) $row['attempt'] : 0,
            maxAttempts: is_numeric($row['max_attempts'] ?? null) ? (int) $row['max_attempts'] : 3,
            generation: is_numeric($row['generation'] ?? null) ? (int) $row['generation'] : 0,
            leaseToken: is_string($row['lease_token'] ?? null) ? $row['lease_token'] : null,
            availableAt: is_numeric($row['available_at'] ?? null) ? (int) $row['available_at'] : 0,
            leaseUntil: is_numeric($row['lease_until'] ?? null) ? (int) $row['lease_until'] : null,
            status: JobStatus::tryFrom(is_string($row['status'] ?? null) ? $row['status'] : '') ?? JobStatus::Pending,
            lastError: is_string($row['last_error'] ?? null) ? $row['last_error'] : null,
            uniqueKey: is_string($row['unique_key'] ?? null) && '' !== $row['unique_key'] ? $row['unique_key'] : null,
        );
    }
}
