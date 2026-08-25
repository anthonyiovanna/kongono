<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;

/** Reports immediate failure to acquire the transaction-scoped provider lock. */
#[Api(ApiStatus::Experimental)]
final class MigrationLockUnavailableException extends MigrationException
{
    public static function unavailable(MigrationDirection $direction, ?string $profile = null): self
    {
        return new self(MigrationDiagnostic::LockUnavailable, $direction, lifecyclePhase: 'lock', profile: $profile);
    }
}
