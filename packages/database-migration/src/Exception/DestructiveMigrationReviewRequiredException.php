<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;
use Kongono\DatabaseMigration\MigrationRisk;

/** Stops destructive work until the caller supplies explicit review policy. */
#[Api(ApiStatus::Experimental)]
final class DestructiveMigrationReviewRequiredException extends MigrationException
{
    public static function denied(MigrationDirection $direction): self
    {
        return new self(MigrationDiagnostic::DestructiveReviewRequired, $direction, risk: MigrationRisk::Destructive);
    }
}
