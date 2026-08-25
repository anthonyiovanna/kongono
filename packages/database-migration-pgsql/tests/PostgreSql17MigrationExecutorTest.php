<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigrationPgsql\Tests;

use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseCapability;
use Kongono\Database\DatabaseColumn;
use Kongono\Database\DatabaseRow;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\DatabaseValue;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionAccess;
use Kongono\Database\TransactionIsolation;
use Kongono\Database\TransactionOptions;
use Kongono\Database\TransactionState;
use Kongono\DatabaseMigration\CreateTable;
use Kongono\DatabaseMigration\DestructiveMigrationPolicy;
use Kongono\DatabaseMigration\Exception\CombinedMigrationFailureException;
use Kongono\DatabaseMigration\Exception\MigrationExecutionException;
use Kongono\DatabaseMigration\Exception\MigrationLockUnavailableException;
use Kongono\DatabaseMigration\Exception\MigrationUncertainStateException;
use Kongono\DatabaseMigration\MigrationCatalog;
use Kongono\DatabaseMigration\MigrationDefinition;
use Kongono\DatabaseMigration\MigrationDirectionPlan;
use Kongono\DatabaseMigration\MigrationRisk;
use Kongono\DatabaseMigration\SchemaColumn;
use Kongono\DatabaseMigration\SchemaColumnType;
use Kongono\DatabaseMigrationPgsql\PostgreSql17MigrationExecutor;
use Kongono\DatabaseMigrationPgsql\PostgreSqlMigrationConfiguration;
use Kongono\DatabaseSql\SqlIdentifier;
use Kongono\DatabaseSql\SqlQualifiedName;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class PostgreSql17MigrationExecutorTest extends TestCase
{
    public function testApplyUsesExactTransactionSequenceAndRetainsCallerSession(): void
    {
        $transaction = new MigrationTestTransaction([
            MigrationTestRows::one(DatabaseValue::boolean(true)),
            new CommandResult(0),
            MigrationTestRows::empty(),
            new CommandResult(0),
            new CommandResult(1),
        ]);
        $session = new MigrationTestSession($transaction);
        $result = $this->executor()->applyPending($session, $this->catalog(), DestructiveMigrationPolicy::Deny);

        self::assertSame(['begin', 'execute', 'execute', 'execute', 'execute', 'execute', 'commit', 'close'], $transaction->events);
        self::assertNotNull($session->options);
        self::assertSame(TransactionIsolation::Serializable, $session->options->isolation());
        self::assertSame(TransactionAccess::ReadWrite, $session->options->access());
        self::assertSame([0, 1, 1, 1], [$result->previousAppliedCount(), $result->resultingAppliedCount(), $result->changedMigrationCount(), $result->executedOperationCount()]);
        self::assertFalse($session->isClosed());
        self::assertSame(0, $session->closeCount);
    }

    public function testNoOpStillLocksReadsAndCommits(): void
    {
        $definition = $this->catalog()->definitions()[0];
        $transaction = new MigrationTestTransaction([
            MigrationTestRows::one(DatabaseValue::boolean(true)),
            new CommandResult(0),
            MigrationTestRows::oneRow([
                DatabaseValue::integer(1),
                DatabaseValue::text($definition->id()),
                DatabaseValue::text($definition->sha256()),
            ]),
        ]);
        $result = $this->executor()->applyPending(new MigrationTestSession($transaction), new MigrationCatalog([$definition]), DestructiveMigrationPolicy::Deny);

        self::assertTrue($result->isNoOp());
        self::assertSame(['begin', 'execute', 'execute', 'execute', 'commit', 'close'], $transaction->events);
    }

    public function testLockDenialRollsBackOnceWithoutApplicationDdl(): void
    {
        $transaction = new MigrationTestTransaction([MigrationTestRows::one(DatabaseValue::boolean(false))]);

        try {
            $this->executor()->applyPending(new MigrationTestSession($transaction), $this->catalog(), DestructiveMigrationPolicy::Deny);
            self::fail('Expected lock denial.');
        } catch (MigrationLockUnavailableException $failure) {
            self::assertSame('KGN-MIGRATION-1005', $failure->diagnosticId());
        }
        self::assertSame(['begin', 'execute', 'rollback', 'close'], $transaction->events);
    }

    public function testExecutionFailureRollsBackAndPreservesPrimary(): void
    {
        $provider = new RuntimeException('provider sentinel');
        $transaction = new MigrationTestTransaction([
            MigrationTestRows::one(DatabaseValue::boolean(true)),
            new CommandResult(0),
            MigrationTestRows::empty(),
            $provider,
        ]);

        try {
            $this->executor()->applyPending(new MigrationTestSession($transaction), $this->catalog(), DestructiveMigrationPolicy::Deny);
            self::fail('Expected execution failure.');
        } catch (MigrationExecutionException $failure) {
            self::assertSame($provider, $failure->getPrevious());
            self::assertStringNotContainsString('provider sentinel', (string) $failure);
        }
        self::assertSame(['begin', 'execute', 'execute', 'execute', 'execute', 'rollback', 'close'], $transaction->events);
    }

    public function testRollbackFailureProducesCombinedFailureWithoutRetry(): void
    {
        $transaction = new MigrationTestTransaction([
            MigrationTestRows::one(DatabaseValue::boolean(true)),
            new CommandResult(0),
            MigrationTestRows::empty(),
            new RuntimeException('primary'),
        ]);
        $transaction->rollbackFailure = new RuntimeException('rollback');

        try {
            $this->executor()->applyPending(new MigrationTestSession($transaction), $this->catalog(), DestructiveMigrationPolicy::Deny);
            self::fail('Expected combined failure.');
        } catch (CombinedMigrationFailureException $failure) {
            self::assertInstanceOf(MigrationExecutionException::class, $failure->primaryFailure());
            self::assertSame($transaction->rollbackFailure, $failure->cleanupFailure());
        }
        self::assertSame(1, $transaction->rollbackCount);
        self::assertSame(1, $transaction->closeCount);
    }

    public function testCommitFailureIsUncertainAndNeverRolledBack(): void
    {
        $transaction = new MigrationTestTransaction([
            MigrationTestRows::one(DatabaseValue::boolean(true)),
            new CommandResult(0),
            MigrationTestRows::empty(),
            new CommandResult(0),
            new CommandResult(1),
        ]);
        $transaction->commitFailure = new RuntimeException('commit');

        $this->expectException(MigrationUncertainStateException::class);
        try {
            $this->executor()->applyPending(new MigrationTestSession($transaction), $this->catalog(), DestructiveMigrationPolicy::Deny);
        } finally {
            self::assertSame(0, $transaction->rollbackCount);
            self::assertSame(1, $transaction->commitCount);
        }
    }

    public function testMissingCapabilitiesFailBeforeBegin(): void
    {
        $session = new MigrationTestSession(new MigrationTestTransaction([]), new DatabaseCapabilities([DatabaseCapability::Transactions]));

        $this->expectException(MigrationExecutionException::class);
        try {
            $this->executor()->applyPending($session, $this->catalog(), DestructiveMigrationPolicy::Deny);
        } finally {
            self::assertSame(0, $session->beginCount);
        }
    }

    private function executor(): PostgreSql17MigrationExecutor
    {
        return new PostgreSql17MigrationExecutor(new PostgreSqlMigrationConfiguration(
            new SqlQualifiedName([new SqlIdentifier('migration_history')]),
            PHP_INT_MIN,
        ));
    }

    private function catalog(): MigrationCatalog
    {
        $identifier = new SqlIdentifier('record_id');

        return new MigrationCatalog([new MigrationDefinition(1, 'create_records', new MigrationDirectionPlan([
            new CreateTable(
                new SqlQualifiedName([new SqlIdentifier('records')]),
                [new SchemaColumn($identifier, SchemaColumnType::Int64, false)],
                [$identifier],
            ),
        ], MigrationRisk::Safe))]);
    }
}

final class MigrationTestSession implements DatabaseSession
{
    public int $beginCount = 0;
    public int $closeCount = 0;
    public ?TransactionOptions $options = null;

    public function __construct(
        private readonly MigrationTestTransaction $transaction,
        private readonly DatabaseCapabilities $reportedCapabilities = new DatabaseCapabilities([
            DatabaseCapability::Transactions,
            DatabaseCapability::IsolationSerializable,
        ]),
    ) {}

    public function capabilities(): DatabaseCapabilities
    {
        return $this->reportedCapabilities;
    }

    public function begin(TransactionOptions $options): DatabaseTransaction
    {
        ++$this->beginCount;
        $this->options = $options;
        $this->transaction->events[] = 'begin';

        return $this->transaction;
    }

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        throw new RuntimeException('caller session execute is not expected');
    }

    public function close(): void
    {
        ++$this->closeCount;
    }

    public function isClosed(): bool
    {
        return $this->closeCount > 0;
    }
}

final class MigrationTestTransaction implements DatabaseTransaction
{
    /** @var list<string> */
    public array $events = [];
    public int $commitCount = 0;
    public int $rollbackCount = 0;
    public int $closeCount = 0;
    public ?Throwable $commitFailure = null;
    public ?Throwable $rollbackFailure = null;
    public ?Throwable $closeFailure = null;
    private TransactionState $transactionState = TransactionState::Active;

    /** @param list<CommandResult|RowStream|Throwable> $results */
    public function __construct(private array $results) {}

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        $this->events[] = 'execute';
        $result = array_shift($this->results);
        if ($result instanceof Throwable) {
            throw $result;
        }
        if (!$result instanceof RowStream && !$result instanceof CommandResult) {
            throw new RuntimeException('missing transaction result');
        }

        return $result;
    }

    public function state(): TransactionState
    {
        return $this->transactionState;
    }

    public function commit(): void
    {
        ++$this->commitCount;
        $this->events[] = 'commit';
        if (null !== $this->commitFailure) {
            $this->transactionState = TransactionState::Failed;
            throw $this->commitFailure;
        }
        $this->transactionState = TransactionState::Committed;
    }

    public function rollback(): void
    {
        ++$this->rollbackCount;
        $this->events[] = 'rollback';
        if (null !== $this->rollbackFailure) {
            throw $this->rollbackFailure;
        }
        $this->transactionState = TransactionState::RolledBack;
    }

    public function close(): void
    {
        ++$this->closeCount;
        $this->events[] = 'close';
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }

    public function isClosed(): bool
    {
        return TransactionState::Active !== $this->transactionState;
    }
}

final class MigrationTestRows implements RowStream
{
    /** @var list<DatabaseRow> */
    private array $rows;
    private bool $closed = false;

    /** @param list<DatabaseRow> $rows */
    private function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function one(DatabaseValue $value): self
    {
        return self::oneRow([$value]);
    }

    /** @param list<DatabaseValue> $values */
    public static function oneRow(array $values): self
    {
        $columns = [];
        foreach ($values as $ordinal => $_value) {
            $columns[] = new DatabaseColumn($ordinal, 'column_' . $ordinal);
        }

        return new self([new DatabaseRow($columns, $values)]);
    }

    public function columns(): array
    {
        return [];
    }

    public function fetch(): ?DatabaseRow
    {
        $row = array_shift($this->rows);
        if (null === $row) {
            $this->closed = true;
        }

        return $row;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }
}
