<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Throwable;

/** Reports guarded generated-state publication while retaining cleanup causality. */
#[Api(ApiStatus::Experimental)]
final class GeneratedPersistenceStatePublicationException extends PersistenceBuildException
{
    private function __construct(
        private readonly ?Throwable $primaryFailure,
        private readonly ?Throwable $cleanupFailure,
    ) {
        parent::__construct(
            PersistenceBuildDiagnostic::GeneratedStatePublicationFailed,
            null === $cleanupFailure ? PersistenceBuildFailureCategory::GeneratedPublication : PersistenceBuildFailureCategory::GeneratedCleanup,
            true,
            $primaryFailure ?? $cleanupFailure,
        );
    }

    public static function failed(?Throwable $primaryFailure = null, ?Throwable $cleanupFailure = null): self
    {
        return new self($primaryFailure, $cleanupFailure);
    }

    public function primaryFailure(): ?Throwable
    {
        return $this->primaryFailure;
    }

    public function cleanupFailure(): ?Throwable
    {
        return $this->cleanupFailure;
    }
}
