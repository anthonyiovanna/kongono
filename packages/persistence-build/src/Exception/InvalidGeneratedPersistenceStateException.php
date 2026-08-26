<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Throwable;

/** Reports fixed non-disclosing generated-state input, grammar, current, or value failures. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedPersistenceStateException extends PersistenceBuildException
{
    public static function input(?Throwable $previous = null): self
    {
        return new self(
            PersistenceBuildDiagnostic::InvalidGenerationInput,
            PersistenceBuildFailureCategory::GenerationInput,
            false,
            $previous,
        );
    }

    public static function invalid(PersistenceBuildFailureCategory $category = PersistenceBuildFailureCategory::GeneratedStructure): self
    {
        return new self(PersistenceBuildDiagnostic::InvalidGeneratedState, $category, true);
    }

    public static function stale(): self
    {
        return new self(PersistenceBuildDiagnostic::StaleGeneratedState, PersistenceBuildFailureCategory::GeneratedCurrent);
    }

    public static function value(): self
    {
        return new self(PersistenceBuildDiagnostic::InvalidGeneratedStateValue, PersistenceBuildFailureCategory::GeneratedValue, true);
    }
}
