<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\CommandResult;
use Kongono\Database\DatabaseCapabilities;
use Kongono\Database\DatabaseConnector;
use Kongono\Database\DatabaseSession;
use Kongono\Database\DatabaseStatement;
use Kongono\Database\DatabaseTransaction;
use Kongono\Database\RowStream;
use Kongono\Database\TransactionOptions;
use Kongono\DatabaseApplication\Exception\DatabaseSessionUnavailableException;

/** Opens and owns exactly one connector session for one generated operation. */
#[Api(ApiStatus::Experimental)]
final class OperationDatabaseSession implements DatabaseSession
{
    private readonly DatabaseSession $session;
    private bool $unavailable = false;

    public function __construct(DatabaseConnector $connector)
    {
        $this->session = $connector->openSession();
    }

    public function capabilities(): DatabaseCapabilities
    {
        return $this->available()->capabilities();
    }

    public function execute(DatabaseStatement $statement): RowStream|CommandResult
    {
        return $this->available()->execute($statement);
    }

    public function begin(TransactionOptions $options): DatabaseTransaction
    {
        return $this->available()->begin($options);
    }

    public function close(): void
    {
        if ($this->unavailable) {
            return;
        }

        $this->unavailable = true;
        $this->session->close();
    }

    public function isClosed(): bool
    {
        return $this->unavailable;
    }

    private function available(): DatabaseSession
    {
        if ($this->unavailable) {
            throw new DatabaseSessionUnavailableException();
        }

        return $this->session;
    }
}
