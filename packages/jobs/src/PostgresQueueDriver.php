<?php

declare(strict_types=1);

namespace Kongono\Jobs;

use JsonException;
use Kongono\Database\ConnectionInterface;

/**
 * Persistent PostgreSQL queue driver using short `FOR UPDATE SKIP LOCKED`
 * reservation transactions (never held while handlers execute) and generation/token fencing.
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
            . 'updated_at BIGINT NOT NULL'
            . ')',
            $t,
        ));
    }

    public function push(
        string $queue,
        string $type,
        array $payload = [],
        int $delaySeconds = 0,
        int $maxAttempts = 3,
        ?int $now = null,
    ): Job {
        $ts = $now ?? time();
        $id = bin2hex(random_bytes(16));
        $availableAt = $ts + max(0, $delaySeconds);
        $attempts = max(1, $maxAttempts);

        $this->db->table($this->table)->insert([
            'id' => $id,
            'queue' => $queue,
            'type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES, 64),
            'attempt' => 0,
            'max_attempts' => $attempts,
            'generation' => 0,
            'lease_token' => null,
            'available_at' => $availableAt,
            'lease_until' => null,
            'status' => JobStatus::Pending->value,
            'last_error' => null,
            'updated_at' => $ts,
        ]);

        return new Job($id, $queue, $type, $payload, 0, $attempts, 0, null, $availableAt, null, JobStatus::Pending);
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
        );
    }
}
