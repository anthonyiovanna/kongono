<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;
use Throwable;

/** Wraps one provider failure at a known migration execution phase. */
#[Api(ApiStatus::Experimental)]
final class MigrationExecutionException extends MigrationException
{
    public static function failed(
        MigrationDirection $direction,
        string $phase,
        ?Throwable $previous = null,
        ?string $profile = null,
    ): self {
        return new self(MigrationDiagnostic::Execution, $direction, lifecyclePhase: $phase, profile: $profile, previous: $previous);
    }
}
