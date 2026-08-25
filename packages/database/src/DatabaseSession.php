<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns one provider session for one explicit operation boundary. */
#[Api(ApiStatus::Public)]
interface DatabaseSession extends DatabaseExecutor
{
    public function capabilities(): DatabaseCapabilities;

    public function begin(TransactionOptions $options): DatabaseTransaction;

    public function close(): void;

    public function isClosed(): bool;
}
