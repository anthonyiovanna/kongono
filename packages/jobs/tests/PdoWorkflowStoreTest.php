<?php

declare(strict_types=1);

namespace Kongono\Jobs\Tests;

use Closure;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\Dialect;
use Kongono\Database\QueryBuilder;
use Kongono\Jobs\Workflow\PdoWorkflowStore;
use Kongono\Jobs\Workflow\WorkflowContext;
use Kongono\Jobs\Workflow\WorkflowEngine;
use Kongono\Jobs\Workflow\WorkflowStatus;
use PHPUnit\Framework\TestCase;

final class PdoWorkflowStoreTest extends TestCase
{
    public function testDurableMultiMonthSleepWakeupAndSignalSurviveEngineRestarts(): void
    {
        $db = $this->createInMemorySqlConnection(Dialect::Postgres);
        $store = new PdoWorkflowStore($db);
        $store->installSchema();

        self::assertTrue(self::anySqlContains($db->queries, 'CREATE TABLE IF NOT EXISTS "kongono_workflows"'));
        self::assertTrue(self::anySqlContains($db->queries, 'CREATE INDEX IF NOT EXISTS "idx_kongono_workflows_status_wake"'));

        $stepExecutions = 0;
        $definition = static function (WorkflowContext $wf) use (&$stepExecutions): array {
            $notice = $wf->step('send_renewal_notice', static function () use (&$stepExecutions): string {
                ++$stepExecutions;

                return 'notice_sent_day_0';
            });

            // Sleep 30 days (simulating statutory notice / lease renewal countdown)
            $wf->sleep(30 * 86_400, 'grace_30_days');

            // Wait for tenant 1-tap renewal signal
            $decision = $wf->waitForEvent('tenant_decision', timeoutSeconds: 14 * 86_400);

            return [
                'notice' => $notice,
                'decision' => $decision,
            ];
        };

        $t0 = 1_700_000_000;
        $engine1 = new WorkflowEngine($store);
        $state1 = $engine1->run('lease-renewal-101', 'LeaseRenewalWorkflow', $definition, now: $t0);

        self::assertSame(WorkflowStatus::Sleeping, $state1->status);
        self::assertSame(1, $stepExecutions);
        self::assertSame($t0 + (30 * 86_400), $state1->wakeAt);
        self::assertTrue(self::anySqlContains($db->queries, 'ON CONFLICT (run_id) DO UPDATE SET'));

        // Before 30 days: not due yet
        self::assertCount(0, $store->listDueWakeups(now: $t0 + (29 * 86_400)));
        self::assertCount(1, $store->listByStatus(WorkflowStatus::Sleeping, 'LeaseRenewalWorkflow'));

        // At day 30: due for wakeup in a brand-new WorkflowEngine instance (simulating full server restart)
        $day30 = $t0 + (30 * 86_400);
        $due = $store->listDueWakeups(now: $day30);
        self::assertCount(1, $due);
        self::assertSame('lease-renewal-101', $due[0]->runId);

        $engine2 = new WorkflowEngine($store);
        $state2 = $engine2->run('lease-renewal-101', 'LeaseRenewalWorkflow', $definition, now: $day30);
        self::assertSame(WorkflowStatus::WaitingForEvent, $state2->status);
        self::assertSame('tenant_decision', $state2->waitingEvent);
        // Memoized step MUST NOT re-execute!
        self::assertSame(1, $stepExecutions);

        $waiting = $store->listWaitingForEvent('tenant_decision', 'LeaseRenewalWorkflow');
        self::assertCount(1, $waiting);
        self::assertSame('lease-renewal-101', $waiting[0]->runId);

        // Deliver external tenant renewal signal and complete in a third fresh engine instance
        $engine3 = new WorkflowEngine($store);
        $engine3->signal('lease-renewal-101', 'tenant_decision', ['action' => 'renew_12m'], now: $day30 + 3600);
        $completed = $engine3->run('lease-renewal-101', 'LeaseRenewalWorkflow', $definition, now: $day30 + 3605);

        self::assertSame(WorkflowStatus::Completed, $completed->status);
        self::assertSame(1, $stepExecutions);
        self::assertSame([
            'notice' => 'notice_sent_day_0',
            'decision' => ['action' => 'renew_12m'],
        ], $completed->output);
        self::assertCount(1, $store->listByStatus(WorkflowStatus::Completed));

        $store->delete('lease-renewal-101');
        self::assertNull($store->load('lease-renewal-101'));
    }

    public function testMysqlDialectEmitsInlineIndexesAndOnDuplicateKeyUpsert(): void
    {
        $mysql = $this->createInMemorySqlConnection(Dialect::Mysql);
        $store = new PdoWorkflowStore($mysql, 'app_workflows');
        $store->installSchema();

        self::assertTrue(self::anySqlContains($mysql->queries, 'INDEX `idx_app_workflows_status_wake` (status, wake_at)'));
        self::assertFalse(self::anySqlContains($mysql->queries, 'CREATE INDEX IF NOT EXISTS'));

        $engine = new WorkflowEngine($store);
        $done = $engine->run('mysql-run-1', 'DemoWorkflow', static fn(WorkflowContext $wf): string => 'ok');
        self::assertSame(WorkflowStatus::Completed, $done->status);
        self::assertTrue(self::anySqlContains($mysql->queries, 'ON DUPLICATE KEY UPDATE'));
    }

    /** @param list<string> $queries */
    private static function anySqlContains(array $queries, string $needle): bool
    {
        foreach ($queries as $sql) {
            if (str_contains($sql, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function createInMemorySqlConnection(Dialect $dialect): ConnectionInterface
    {
        return new class ($dialect) implements ConnectionInterface {
            /** @var list<string> */
            public array $queries = [];

            /** @var array<string, array{run_id: string, workflow_name: string, status: string, state_json: string, wake_at: ?int, waiting_event: ?string, attempts: int, updated_at: int}> */
            private array $rows = [];

            public function __construct(
                private readonly Dialect $dialect,
            ) {}

            public function dialect(): Dialect
            {
                return $this->dialect;
            }

            public function table(string $table, ?string $alias = null): QueryBuilder
            {
                return new QueryBuilder($table, $this->dialect, $this, $alias);
            }

            public function query(string $sql, array $bindings = []): array
            {
                $this->queries[] = $sql;
                $matches = [];

                if (str_contains($sql, 'wake_at IS NOT NULL AND wake_at <= ?')) {
                    $status1 = (string) ($bindings[0] ?? '');
                    $status2 = (string) ($bindings[1] ?? '');
                    $now = is_int($bindings[2] ?? null) ? $bindings[2] : 0;
                    foreach ($this->rows as $row) {
                        if (($row['status'] === $status1 || $row['status'] === $status2) && null !== $row['wake_at'] && $row['wake_at'] <= $now) {
                            $matches[] = $row;
                        }
                    }
                    usort($matches, static fn(array $a, array $b): int => ($a['wake_at'] ?? 0) <=> ($b['wake_at'] ?? 0));
                } elseif (str_contains($sql, 'waiting_event = ?')) {
                    $status = (string) ($bindings[0] ?? '');
                    $event = (string) ($bindings[1] ?? '');
                    $wfName = isset($bindings[2]) ? (string) $bindings[2] : null;
                    foreach ($this->rows as $row) {
                        if ($row['status'] === $status && $row['waiting_event'] === $event && (null === $wfName || $row['workflow_name'] === $wfName)) {
                            $matches[] = $row;
                        }
                    }
                } elseif (str_contains($sql, 'WHERE status = ?')) {
                    $status = (string) ($bindings[0] ?? '');
                    $wfName = isset($bindings[1]) ? (string) $bindings[1] : null;
                    foreach ($this->rows as $row) {
                        if ($row['status'] === $status && (null === $wfName || $row['workflow_name'] === $wfName)) {
                            $matches[] = $row;
                        }
                    }
                } elseif (str_contains($sql, 'WHERE run_id = ?')) {
                    $runId = (string) ($bindings[0] ?? '');
                    if (isset($this->rows[$runId])) {
                        $matches[] = $this->rows[$runId];
                    }
                }

                return array_map(static fn(array $r): array => ['state_json' => $r['state_json']], $matches);
            }

            public function first(string $sql, array $bindings = []): ?array
            {
                $rows = $this->query($sql, $bindings);

                return $rows[0] ?? null;
            }

            public function execute(string $sql, array $bindings = []): int
            {
                $this->queries[] = $sql;
                if (str_starts_with($sql, 'INSERT INTO')) {
                    $runId = (string) ($bindings[0] ?? '');
                    $this->rows[$runId] = [
                        'run_id' => $runId,
                        'workflow_name' => (string) ($bindings[1] ?? ''),
                        'status' => (string) ($bindings[2] ?? ''),
                        'state_json' => (string) ($bindings[3] ?? ''),
                        'wake_at' => is_int($bindings[4] ?? null) ? $bindings[4] : null,
                        'waiting_event' => is_string($bindings[5] ?? null) ? $bindings[5] : null,
                        'attempts' => is_int($bindings[6] ?? null) ? $bindings[6] : 0,
                        'updated_at' => is_int($bindings[7] ?? null) ? $bindings[7] : 0,
                    ];

                    return 1;
                }
                if (str_starts_with($sql, 'DELETE FROM')) {
                    $runId = (string) ($bindings[0] ?? '');
                    unset($this->rows[$runId]);

                    return 1;
                }

                return 0;
            }

            public function transaction(Closure $callback): mixed
            {
                return $callback($this);
            }

            public function lastInsertId(): ?string
            {
                return null;
            }
        };
    }
}
