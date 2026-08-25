<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Diagnostic\MigrationDiagnostic;

/** Reports a definition that violates the closed bounded migration vocabulary. */
#[Api(ApiStatus::Experimental)]
final class InvalidMigrationDefinitionException extends MigrationException
{
    public static function invalidDefinition(): self
    {
        return new self(MigrationDiagnostic::InvalidDefinition);
    }
}
