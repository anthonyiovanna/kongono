<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;

/** Reports rejected aggregate repository, load, or cascade evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidAggregatePersistenceException extends PersistenceException
{
    public static function invalid(): self
    {
        return new self(PersistenceDiagnostic::AggregateRepositoryEvidence);
    }
}
