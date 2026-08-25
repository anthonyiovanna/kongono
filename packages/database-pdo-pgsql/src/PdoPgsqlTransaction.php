<?php

declare(strict_types=1);

namespace Kongono\DatabasePdoPgsql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\Exception\DatabaseTransactionCommitException;
use Kongono\Database\Exception\DatabaseTransactionRollbackException;
use Kongono\Database\Exception\InvalidDatabaseStateException;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionState;
use Throwable;

/** Owns one explicit terminal PostgreSQL transaction and no ambient state. */
#[Api(ApiStatus::Internal)]
final class PdoPgsqlTransaction implements DatabaseTransaction
{
    private TransactionState $state = TransactionState::Active;

    public function __construct(private readonly PdoPgsqlSession $session) {}

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        if (TransactionState::Active !== $this->state) {
            throw InvalidDatabaseStateException::lifecycle();
        }

        return $this->session->executeTransaction($this, $statement);
    }

    public function state(): TransactionState
    {
        return $this->state;
    }

    public function commit(): void
    {
        if (TransactionState::Active !== $this->state) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        try {
            $this->session->nativeCommit($this);
        } catch (InvalidDatabaseStateException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->state = TransactionState::Failed;
            throw DatabaseTransactionCommitException::failed($exception);
        }
        $this->state = TransactionState::Committed;
        $this->session->releaseTransaction($this);
    }

    public function rollback(): void
    {
        if (TransactionState::Active !== $this->state) {
            throw InvalidDatabaseStateException::lifecycle();
        }
        try {
            $this->session->nativeRollback($this);
        } catch (InvalidDatabaseStateException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->state = TransactionState::Failed;
            throw DatabaseTransactionRollbackException::failed($exception);
        }
        $this->state = TransactionState::RolledBack;
        $this->session->releaseTransaction($this);
    }

    public function close(): void
    {
        if (TransactionState::Active === $this->state) {
            $this->rollback();
        }
    }

    public function isClosed(): bool
    {
        return TransactionState::Active !== $this->state;
    }
}
