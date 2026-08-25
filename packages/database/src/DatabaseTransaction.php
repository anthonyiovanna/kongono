<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns explicit one-attempt transaction execution and terminal cleanup. */
#[Api(ApiStatus::Public)]
interface DatabaseTransaction extends DatabaseExecutor
{
    public function state(): TransactionState;

    public function commit(): void;

    public function rollback(): void;

    public function close(): void;

    public function isClosed(): bool;
}
