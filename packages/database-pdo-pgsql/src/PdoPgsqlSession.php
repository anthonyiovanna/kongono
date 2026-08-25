<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseCapability;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\Exception\CombinedDatabaseFailureException;
use Kongono\Database\Exception\DatabaseTransactionBeginException;
use Kongono\Database\Exception\InvalidDatabaseStateException;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionAccess;
use Kongono\Database\TransactionIsolation;
use Kongono\Database\TransactionOptions;
use PDO;
use Throwable;

/** Owns one PDO connection and the adapter's one-stream/one-transaction state. */
#[Api(ApiStatus::Internal)]
final class PdoPgsqlSession implements DatabaseSession
{
    private readonly DatabaseCapabilities $capabilities;
    private ?PdoPgsqlRowStream $activeStream = null;
    private ?PdoPgsqlTransaction $activeTransaction = null;
    private bool $closed = false;

    public function __construct(private ?PDO $pdo)
    {
        $this->capabilities = new DatabaseCapabilities(DatabaseCapability::cases());
    }

    public function capabilities(): DatabaseCapabilities
    {
        return $this->capabilities;
    }

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        $this->requireOpenAndIdle();
        if (null !== $this->activeTransaction) {
            throw InvalidDatabaseStateException::lifecycle();
        }

        return $this->executeOwned($statement);
    }

    public function begin(TransactionOptions $options): DatabaseTransaction
    {
        $this->requireOpenAndIdle();
        if (null !== $this->activeTransaction || null === $this->pdo) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        $sql = $this->beginSql($options);
        try {
            if (false === $this->pdo->exec($sql)) {
                throw DatabaseTransactionBeginException::failed();
            }
        } catch (DatabaseTransactionBeginException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw DatabaseTransactionBeginException::failed($exception);
        }
        $transaction = new PdoPgsqlTransaction($this);
        $this->activeTransaction = $transaction;

        return $transaction;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $failure = null;
        if (null !== $this->activeStream) {
            try {
                $this->activeStream->close();
            } catch (Throwable $exception) {
                $failure = $exception;
            }
        }
        if (null !== $this->activeTransaction) {
            try {
                $this->activeTransaction->close();
            } catch (Throwable $exception) {
                $failure = null === $failure ? $exception : new CombinedDatabaseFailureException($failure, $exception);
            }
        }
        $this->pdo = null;
        $this->closed = true;
        if (null !== $failure) {
            throw $failure;
        }
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function executeTransaction(PdoPgsqlTransaction $transaction, DatabaseStatement $statement): RowStream|CommandResult
    {
        if ($transaction !== $this->activeTransaction || $transaction->isClosed()) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        $this->requireOpenAndIdle();

        return $this->executeOwned($statement);
    }

    public function nativeCommit(PdoPgsqlTransaction $transaction): void
    {
        if ($transaction !== $this->activeTransaction || null === $this->pdo || null !== $this->activeStream) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        if (false === $this->pdo->exec('COMMIT')) {
            throw new \RuntimeException('commit_failed');
        }
    }

    public function nativeRollback(PdoPgsqlTransaction $transaction): void
    {
        if ($transaction !== $this->activeTransaction || null === $this->pdo || null !== $this->activeStream) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        if (false === $this->pdo->exec('ROLLBACK')) {
            throw new \RuntimeException('rollback_failed');
        }
    }

    public function releaseTransaction(PdoPgsqlTransaction $transaction): void
    {
        if ($transaction === $this->activeTransaction) {
            $this->activeTransaction = null;
        }
    }

    private function executeOwned(DatabaseStatement $statement): RowStream|CommandResult
    {
        if (null === $this->pdo) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        $result = PdoPgsqlExecution::execute(
            $this->pdo,
            $statement,
            function (PdoPgsqlRowStream $stream): void {
                if ($stream === $this->activeStream) {
                    $this->activeStream = null;
                }
            },
        );
        if ($result instanceof PdoPgsqlRowStream) {
            $this->activeStream = $result;
        }

        return $result;
    }

    private function requireOpenAndIdle(): void
    {
        if ($this->closed || null === $this->pdo || null !== $this->activeStream) {
            throw InvalidDatabaseStateException::lifecycle();
        }
    }

    private function beginSql(TransactionOptions $options): string
    {
        $isolation = match ($options->isolation()) {
            TransactionIsolation::Default => '',
            TransactionIsolation::ReadCommitted => ' ISOLATION LEVEL READ COMMITTED',
            TransactionIsolation::RepeatableRead => ' ISOLATION LEVEL REPEATABLE READ',
            TransactionIsolation::Serializable => ' ISOLATION LEVEL SERIALIZABLE',
        };
        $access = match ($options->access()) {
            TransactionAccess::ReadWrite => ' READ WRITE',
            TransactionAccess::ReadOnly => ' READ ONLY',
        };

        return 'BEGIN' . $isolation . $access;
    }
}
