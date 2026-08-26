<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Fixture;

use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\TransactionOptions;
use Kongono\Database\TransactionState;

final class RecordingDatabaseAuthority implements DatabaseSession, DatabaseTransaction
{
    public int $executeCalls = 0;
    public int $capabilitiesCalls = 0;
    public int $beginCalls = 0;
    public int $stateCalls = 0;
    public int $commitCalls = 0;
    public int $rollbackCalls = 0;
    public int $closeCalls = 0;
    public int $isClosedCalls = 0;

    public function execute(DatabaseStatement $statement): CommandResult
    {
        ++$this->executeCalls;

        return new CommandResult(1);
    }

    public function capabilities(): DatabaseCapabilities
    {
        ++$this->capabilitiesCalls;

        return new DatabaseCapabilities([]);
    }

    public function begin(TransactionOptions $options): DatabaseTransaction
    {
        ++$this->beginCalls;

        return $this;
    }

    public function state(): TransactionState
    {
        ++$this->stateCalls;

        return TransactionState::Active;
    }

    public function commit(): void
    {
        ++$this->commitCalls;
    }

    public function rollback(): void
    {
        ++$this->rollbackCalls;
    }

    public function close(): void
    {
        ++$this->closeCalls;
    }

    public function isClosed(): bool
    {
        ++$this->isClosedCalls;

        return false;
    }

    /** @return array<string, int> */
    public function counters(): array
    {
        return [
            'execute' => $this->executeCalls,
            'capabilities' => $this->capabilitiesCalls,
            'begin' => $this->beginCalls,
            'state' => $this->stateCalls,
            'commit' => $this->commitCalls,
            'rollback' => $this->rollbackCalls,
            'close' => $this->closeCalls,
            'is_closed' => $this->isClosedCalls,
        ];
    }
}
