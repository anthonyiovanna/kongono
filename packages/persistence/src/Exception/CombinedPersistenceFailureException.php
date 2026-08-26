<?php

declare(strict_types=1);

namespace Kongono\Persistence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Diagnostic\PersistenceDiagnostic;
use Throwable;

/** Retains one primary failure together with one later cleanup failure. */
#[Api(ApiStatus::Experimental)]
final class CombinedPersistenceFailureException extends PersistenceException
{
    public function __construct(
        private readonly Throwable $primaryFailure,
        private readonly Throwable $cleanupFailure,
    ) {
        parent::__construct(PersistenceDiagnostic::CombinedFailure, $primaryFailure);
    }

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    public function cleanupFailure(): Throwable
    {
        return $this->cleanupFailure;
    }
}
