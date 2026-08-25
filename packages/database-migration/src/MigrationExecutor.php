<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseSession;

/** Executes explicit ApplyPending or RevertLast behavior on a caller-owned session. */
#[Api(ApiStatus::Public)]
interface MigrationExecutor
{
    public function applyPending(
        DatabaseSession $session,
        MigrationCatalog $catalog,
        DestructiveMigrationPolicy $policy,
    ): MigrationRunResult;

    public function revertLast(
        DatabaseSession $session,
        MigrationCatalog $catalog,
        DestructiveMigrationPolicy $policy,
    ): MigrationRunResult;
}
