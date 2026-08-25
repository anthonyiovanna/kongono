<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;
use Kongono\DatabaseMigration\MigrationDirection;

/** Reports a requested Revert when the definition explicitly has no Revert plan. */
#[Api(ApiStatus::Experimental)]
final class MigrationNotReversibleException extends MigrationException
{
    public static function absent(int $ordinal): self
    {
        return new self(MigrationDiagnostic::NotReversible, MigrationDirection::Revert, $ordinal);
    }
}
