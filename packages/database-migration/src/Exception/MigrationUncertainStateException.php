<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;
use Throwable;

/** Reports a commit or cleanup outcome whose database state cannot be proven. */
#[Api(ApiStatus::Experimental)]
final class MigrationUncertainStateException extends MigrationException
{
    public static function uncertain(
        MigrationDirection $direction,
        string $phase,
        ?Throwable $previous = null,
        ?string $profile = null,
    ): self {
        return new self(MigrationDiagnostic::UncertainOrCleanup, $direction, lifecyclePhase: $phase, profile: $profile, previous: $previous);
    }
}
