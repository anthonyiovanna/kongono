<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;

/** Reports applied history that is not an exact catalog prefix. */
#[Api(ApiStatus::Experimental)]
final class MigrationHistoryMismatchException extends MigrationException
{
    public static function mismatch(MigrationDirection $direction): self
    {
        return new self(MigrationDiagnostic::HistoryMismatch, $direction);
    }
}
