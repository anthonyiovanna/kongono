<?php

declare(strict_types=1);

namespace Kongono\Jobs\Workflow;

use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Jobs\JobException;

/**
 * Persistent SQL durable workflow store (PostgreSQL, SQLite, MySQL).
 *
 * Built for multi-day and multi-month workflows (e.g. lease renewals, statutory deposit deadlines,
 * billing lifecycles) that must survive server restarts and never evict from an LRU cache.
 * Includes indexed `listDueWakeups()` and `listWaitingForEvent()` queries for scheduled resuming.
 */
final readonly class PdoWorkflowStore implements WorkflowStoreInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private string $table = 'kongono_workflows',
    ) {}

    public function installSchema(): void
    {
        $dialect = $this->db->dialect();
        $t = $dialect->quoteIdentifier($this->table);
        $safeName = str_replace('.', '_', $this->table);
        $wakeIdx = $dialect->quoteIdentifier('idx_' . $safeName . '_status_wake');
        $eventIdx = $dialect->quoteIdentifier('idx_' . $safeName . '_waiting_event');

        $mysqlIndexes = Dialect::Mysql === $dialect
            ? sprintf(', INDEX %s (status, wake_at), INDEX %s (waiting_event)', $wakeIdx, $eventIdx)
            : '';

        $this->db->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS %s ('
            . 'run_id VARCHAR(128) PRIMARY KEY, '
            . 'workflow_name VARCHAR(192) NOT NULL, '
            . 'status VARCHAR(32) NOT NULL, '
            . 'state_json TEXT NOT NULL, '
            . 'wake_at BIGINT, '
            . 'waiting_event VARCHAR(192), '
            . 'attempts INTEGER NOT NULL DEFAULT 0, '
            . 'updated_at BIGINT NOT NULL'
            . '%s)',
            $t,
            $mysqlIndexes,
        ));

        if (Dialect::Mysql !== $dialect) {
            $this->db->execute(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (status, wake_at)', $wakeIdx, $t));
            $this->db->execute(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s (waiting_event)', $eventIdx, $t));
        }
    }

    public function load(string $runId): ?WorkflowState
    {
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $row = $this->db->first(sprintf('SELECT state_json FROM %s WHERE run_id = ? LIMIT 1', $t), [$runId]);
        if (null === $row) {
            return null;
        }

        return self::hydrateRow($row);
    }

    public function save(WorkflowState $state): void
    {
        $dialect = $this->db->dialect();
        $t = $dialect->quoteIdentifier($this->table);
        $json = json_encode($state->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $now = time();

        if (Dialect::Mysql === $dialect) {
            $sql = sprintf(
                'INSERT INTO %s (run_id, workflow_name, status, state_json, wake_at, waiting_event, attempts, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE '
                . 'workflow_name = VALUES(workflow_name), status = VALUES(status), state_json = VALUES(state_json), '
                . 'wake_at = VALUES(wake_at), waiting_event = VALUES(waiting_event), attempts = VALUES(attempts), updated_at = VALUES(updated_at)',
                $t,
            );
        } else {
            $sql = sprintf(
                'INSERT INTO %s (run_id, workflow_name, status, state_json, wake_at, waiting_event, attempts, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?) '
                . 'ON CONFLICT (run_id) DO UPDATE SET '
                . 'workflow_name = EXCLUDED.workflow_name, status = EXCLUDED.status, state_json = EXCLUDED.state_json, '
                . 'wake_at = EXCLUDED.wake_at, waiting_event = EXCLUDED.waiting_event, attempts = EXCLUDED.attempts, updated_at = EXCLUDED.updated_at',
                $t,
            );
        }

        $this->db->execute($sql, [
            $state->runId,
            $state->workflowName,
            $state->status->value,
            $json,
            $state->wakeAt,
            $state->waitingEvent,
            $state->attempts,
            $now,
        ]);
    }

    public function delete(string $runId): void
    {
        $t = $this->db->dialect()->quoteIdentifier($this->table);
        $this->db->execute(sprintf('DELETE FROM %s WHERE run_id = ?', $t), [$runId]);
    }

    /**
     * List sleeping or event-waiting workflows whose `wake_at` deadline is due (`<= $now`).
     *
     * @return list<WorkflowState>
     */
    public function listDueWakeups(?int $now = null, int $limit = 100): array
    {
        $ts = $now ?? time();
        $cap = min(1000, max(1, $limit));
        $t = $this->db->dialect()->quoteIdentifier($this->table);

        $rows = $this->db->query(
            sprintf(
                'SELECT state_json FROM %s WHERE status IN (?, ?) AND wake_at IS NOT NULL AND wake_at <= ? ORDER BY wake_at ASC LIMIT %d',
                $t,
                $cap,
            ),
            [WorkflowStatus::Sleeping->value, WorkflowStatus::WaitingForEvent->value, $ts],
        );

        return array_map(self::hydrateRow(...), $rows);
    }

    /**
     * List workflow runs matching `$status` (and optionally `$workflowName`), most recently updated first.
     *
     * @return list<WorkflowState>
     */
    public function listByStatus(WorkflowStatus $status, ?string $workflowName = null, int $limit = 100): array
    {
        $cap = min(1000, max(1, $limit));
        $t = $this->db->dialect()->quoteIdentifier($this->table);

        if (null !== $workflowName && '' !== trim($workflowName)) {
            $rows = $this->db->query(
                sprintf('SELECT state_json FROM %s WHERE status = ? AND workflow_name = ? ORDER BY updated_at DESC LIMIT %d', $t, $cap),
                [$status->value, trim($workflowName)],
            );
        } else {
            $rows = $this->db->query(
                sprintf('SELECT state_json FROM %s WHERE status = ? ORDER BY updated_at DESC LIMIT %d', $t, $cap),
                [$status->value],
            );
        }

        return array_map(self::hydrateRow(...), $rows);
    }

    /**
     * List workflow runs currently suspended and waiting for external event `$eventName`.
     *
     * @return list<WorkflowState>
     */
    public function listWaitingForEvent(string $eventName, ?string $workflowName = null, int $limit = 100): array
    {
        $cap = min(1000, max(1, $limit));
        $t = $this->db->dialect()->quoteIdentifier($this->table);

        if (null !== $workflowName && '' !== trim($workflowName)) {
            $rows = $this->db->query(
                sprintf(
                    'SELECT state_json FROM %s WHERE status = ? AND waiting_event = ? AND workflow_name = ? ORDER BY updated_at ASC LIMIT %d',
                    $t,
                    $cap,
                ),
                [WorkflowStatus::WaitingForEvent->value, $eventName, trim($workflowName)],
            );
        } else {
            $rows = $this->db->query(
                sprintf(
                    'SELECT state_json FROM %s WHERE status = ? AND waiting_event = ? ORDER BY updated_at ASC LIMIT %d',
                    $t,
                    $cap,
                ),
                [WorkflowStatus::WaitingForEvent->value, $eventName],
            );
        }

        return array_map(self::hydrateRow(...), $rows);
    }

    /** @param array<string, mixed> $row */
    private static function hydrateRow(array $row): WorkflowState
    {
        $raw = $row['state_json'] ?? null;
        if (!is_string($raw) || '' === $raw) {
            throw new JobException('Persisted workflow row is missing state_json.');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new JobException('Persisted workflow state_json is corrupted.');
        }

        /** @var array<string, mixed> $decoded */
        return WorkflowState::fromArray($decoded);
    }
}
