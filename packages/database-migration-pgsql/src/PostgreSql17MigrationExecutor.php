<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigrationPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapability;
use Kongono\Database\DatabaseParameter;
use Kongono\Database\DatabaseRow;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\DatabaseValue;
use Kongono\Database\ResultExpectation;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionAccess;
use Kongono\Database\TransactionIsolation;
use Kongono\Database\TransactionOptions;
use Kongono\DatabaseMigration\AddColumn;
use Kongono\DatabaseMigration\AppliedMigration;
use Kongono\DatabaseMigration\CreateIndex;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DestructiveMigrationPolicy;
use Kongono\DatabaseMigration\DropColumn;
use Kongono\DatabaseMigration\DropTable;
use Kongono\DatabaseMigration\Exception\CombinedMigrationFailureException;
use Kongono\DatabaseMigration\Exception\ExceptionInterface;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;
use Kongono\DatabaseMigration\Exception\MigrationExecutionException;
use Kongono\DatabaseMigration\Exception\MigrationHistoryMismatchException;
use Kongono\DatabaseMigration\Exception\MigrationLockUnavailableException;
use Kongono\DatabaseMigration\Exception\MigrationUncertainStateException;
use Kongono\DatabaseMigration\MigrationCatalog;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationExecutor;
use Kongono\DatabaseMigration\MigrationHistory;
use Kongono\DatabaseMigration\MigrationOperation;
use Kongono\DatabaseMigration\MigrationPlan;
use Kongono\DatabaseMigration\MigrationPlanner;
use Kongono\DatabaseMigration\MigrationRunResult;
use Kongono\DatabaseMigration\RenameColumn;
use Kongono\DatabaseMigration\RenameTable;
use Kongono\DatabaseMigrationPgsql\Internal\PostgreSqlMigrationSql;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;
use Throwable;

/** Executes exact-prefix PostgreSQL 17 migrations on one caller-owned open session. */
#[Api(ApiStatus::Experimental)]
final readonly class PostgreSql17MigrationExecutor implements MigrationExecutor
{
    private const string PROFILE = 'postgresql-17';

    public function __construct(
        private PostgreSqlMigrationConfiguration $configuration,
        private PostgreSql17MigrationCompiler $compiler = new PostgreSql17MigrationCompiler(),
        private MigrationPlanner $planner = new MigrationPlanner(),
    ) {}

    public function applyPending(
        DatabaseSession $session,
        MigrationCatalog $catalog,
        DestructiveMigrationPolicy $policy,
    ): MigrationRunResult {
        return $this->run($session, $catalog, $policy, MigrationDirection::Apply);
    }

    public function revertLast(
        DatabaseSession $session,
        MigrationCatalog $catalog,
        DestructiveMigrationPolicy $policy,
    ): MigrationRunResult {
        return $this->run($session, $catalog, $policy, MigrationDirection::Revert);
    }

    private function run(
        DatabaseSession $session,
        MigrationCatalog $catalog,
        DestructiveMigrationPolicy $policy,
        MigrationDirection $direction,
    ): MigrationRunResult {
        $capabilities = $session->capabilities();
        if (!$capabilities->has(DatabaseCapability::Transactions) || !$capabilities->has(DatabaseCapability::IsolationSerializable)) {
            throw MigrationExecutionException::failed($direction, 'capability', profile: self::PROFILE);
        }

        try {
            $transaction = $session->begin(new TransactionOptions(TransactionIsolation::Serializable, TransactionAccess::ReadWrite));
        } catch (Throwable $failure) {
            throw MigrationExecutionException::failed($direction, 'begin', $failure, self::PROFILE);
        }

        $commitAttempted = false;
        try {
            $this->acquireLock($transaction, $direction);
            $this->bootstrapHistory($transaction, $direction);
            $history = $this->readHistory($transaction, $direction);
            $plan = MigrationDirection::Apply === $direction
                ? $this->planner->applyPending($catalog, $history, $policy)
                : $this->planner->revertLast($catalog, $history, $policy);
            $this->assertReservedTableIsolation($plan, $direction);
            $this->executePlan($transaction, $plan);
            $commitAttempted = true;
            try {
                $transaction->commit();
            } catch (Throwable $failure) {
                throw MigrationUncertainStateException::uncertain($direction, 'commit', $failure, self::PROFILE);
            }
            try {
                $transaction->close();
            } catch (Throwable $failure) {
                throw MigrationUncertainStateException::uncertain($direction, 'close', $failure, self::PROFILE);
            }

            return new MigrationRunResult(
                $direction,
                $plan->previousAppliedCount(),
                $plan->resultingAppliedCount(),
                count($plan->migrations()),
                $plan->operationCount(),
            );
        } catch (Throwable $primaryFailure) {
            $primary = $primaryFailure instanceof ExceptionInterface
                ? $primaryFailure
                : MigrationExecutionException::failed($direction, 'execute', $primaryFailure, self::PROFILE);
            if ($commitAttempted) {
                try {
                    $transaction->close();
                } catch (Throwable $cleanupFailure) {
                    throw new CombinedMigrationFailureException($primary, $cleanupFailure, self::PROFILE);
                }
                throw $primary;
            }

            $combined = null;
            try {
                $transaction->rollback();
            } catch (Throwable $rollbackFailure) {
                $combined = new CombinedMigrationFailureException($primary, $rollbackFailure, self::PROFILE);
            }
            try {
                $transaction->close();
            } catch (Throwable $closeFailure) {
                throw new CombinedMigrationFailureException($combined ?? $primary, $closeFailure, self::PROFILE);
            }
            if (null !== $combined) {
                throw $combined;
            }
            throw $primary;
        }
    }

    private function acquireLock(DatabaseTransaction $transaction, MigrationDirection $direction): void
    {
        $statement = new DatabaseStatement(
            'SELECT pg_try_advisory_xact_lock(:kgn_lock)',
            [new DatabaseParameter('kgn_lock', DatabaseValue::integer($this->configuration->advisoryLockKey()))],
            ResultExpectation::Rows,
        );
        $row = $this->singleRow($transaction, $statement, $direction);
        if (true !== $row->at(0)->value()) {
            throw MigrationLockUnavailableException::unavailable($direction, self::PROFILE);
        }
    }

    private function bootstrapHistory(DatabaseTransaction $transaction, MigrationDirection $direction): void
    {
        $table = PostgreSqlMigrationSql::qualified($this->configuration->historyTable());
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $table . ' ("ordinal" BIGINT PRIMARY KEY CHECK ("ordinal" > 0), "id" TEXT NOT NULL UNIQUE CHECK (octet_length("id") BETWEEN 1 AND 64), "checksum" TEXT NOT NULL CHECK ("checksum" ~ \'^[a-f0-9]{64}$\'), "applied_at" TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)';
        $this->completion($transaction, new DatabaseStatement($sql, [], ResultExpectation::Completion), $direction);
    }

    private function readHistory(DatabaseTransaction $transaction, MigrationDirection $direction): MigrationHistory
    {
        $table = PostgreSqlMigrationSql::qualified($this->configuration->historyTable());
        $result = $transaction->execute(new DatabaseStatement(
            'SELECT "ordinal", "id", "checksum" FROM ' . $table . ' ORDER BY "ordinal" ASC',
            [],
            ResultExpectation::Rows,
        ));
        if (!$result instanceof RowStream) {
            throw MigrationExecutionException::failed($direction, 'history', profile: self::PROFILE);
        }
        $records = [];
        try {
            while (null !== ($row = $result->fetch())) {
                $ordinal = $row->at(0)->value();
                $id = $row->at(1)->value();
                $checksum = $row->at(2)->value();
                if (!is_int($ordinal) || !is_string($id) || !is_string($checksum)) {
                    throw MigrationExecutionException::failed($direction, 'history', profile: self::PROFILE);
                }
                try {
                    $records[] = new AppliedMigration($ordinal, $id, $checksum);
                } catch (InvalidMigrationDefinitionException) {
                    throw MigrationHistoryMismatchException::mismatch($direction);
                }
            }
        } finally {
            $result->close();
        }

        try {
            return new MigrationHistory($records);
        } catch (InvalidMigrationDefinitionException) {
            throw MigrationHistoryMismatchException::mismatch($direction);
        }
    }

    private function executePlan(DatabaseTransaction $transaction, MigrationPlan $plan): void
    {
        foreach ($plan->migrations() as $migration) {
            foreach ($migration->plan()->operations() as $operation) {
                foreach ($this->compiler->compile($operation) as $statement) {
                    $this->completion($transaction, $statement, $plan->direction());
                }
            }
            $definition = $migration->definition();
            if (MigrationDirection::Apply === $plan->direction()) {
                $this->completion($transaction, new DatabaseStatement(
                    'INSERT INTO ' . PostgreSqlMigrationSql::qualified($this->configuration->historyTable()) . ' ("ordinal", "id", "checksum") VALUES (:kgn_ordinal, :kgn_id, :kgn_checksum)',
                    [
                        new DatabaseParameter('kgn_ordinal', DatabaseValue::integer($definition->ordinal())),
                        new DatabaseParameter('kgn_id', DatabaseValue::text($definition->id())),
                        new DatabaseParameter('kgn_checksum', DatabaseValue::text($definition->sha256())),
                    ],
                    ResultExpectation::Completion,
                ), $plan->direction());
            } else {
                $this->completion($transaction, new DatabaseStatement(
                    'DELETE FROM ' . PostgreSqlMigrationSql::qualified($this->configuration->historyTable()) . ' WHERE "ordinal" = :kgn_ordinal',
                    [new DatabaseParameter('kgn_ordinal', DatabaseValue::integer($definition->ordinal()))],
                    ResultExpectation::Completion,
                ), $plan->direction());
            }
        }
    }

    private function assertReservedTableIsolation(MigrationPlan $plan, MigrationDirection $direction): void
    {
        foreach ($plan->migrations() as $migration) {
            foreach ($migration->plan()->operations() as $operation) {
                foreach ($this->operationTables($operation) as $table) {
                    if ($this->sameName($table, $this->configuration->historyTable())) {
                        throw MigrationExecutionException::failed($direction, 'reserved_history', profile: self::PROFILE);
                    }
                }
            }
        }
    }

    /** @return list<SqlQualifiedName> */
    private function operationTables(MigrationOperation $operation): array
    {
        if ($operation instanceof CreateTable || $operation instanceof DropTable || $operation instanceof AddColumn
            || $operation instanceof DropColumn || $operation instanceof RenameColumn || $operation instanceof CreateIndex) {
            return [$operation->table()];
        }
        if ($operation instanceof RenameTable) {
            $segments = $operation->source()->segments();
            $segments[count($segments) - 1] = $operation->newName();

            return [$operation->source(), new SqlQualifiedName($segments)];
        }

        return [];
    }

    private function sameName(SqlQualifiedName $left, SqlQualifiedName $right): bool
    {
        return array_map(static fn(SqlIdentifier $identifier): string => $identifier->value(), $left->segments())
            === array_map(static fn(SqlIdentifier $identifier): string => $identifier->value(), $right->segments());
    }

    private function completion(
        DatabaseTransaction $transaction,
        DatabaseStatement $statement,
        MigrationDirection $direction,
    ): void {
        $result = $transaction->execute($statement);
        if (!$result instanceof CommandResult) {
            throw MigrationExecutionException::failed($direction, 'result', profile: self::PROFILE);
        }
    }

    private function singleRow(
        DatabaseTransaction $transaction,
        DatabaseStatement $statement,
        MigrationDirection $direction,
    ): DatabaseRow {
        $result = $transaction->execute($statement);
        if (!$result instanceof RowStream) {
            throw MigrationExecutionException::failed($direction, 'result', profile: self::PROFILE);
        }
        try {
            $row = $result->fetch();
            if (null === $row || null !== $result->fetch()) {
                throw MigrationExecutionException::failed($direction, 'result', profile: self::PROFILE);
            }

            return $row;
        } finally {
            $result->close();
        }
    }
}
