<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Diagnostic\ApplicationGraphArtifactDiagnostic;

/** Reports mismatch with a caller-supplied current Graph. */
#[Api(ApiStatus::Experimental)]
final class StaleApplicationGraphArtifactException extends ApplicationGraphArtifactException
{
    public static function graphStateMismatch(): self
    {
        return new self(ApplicationGraphArtifactDiagnostic::StaleArtifact, ['violation' => 'graph_state_mismatch', 'operation' => 'currentness', 'record_kind' => 'graph']);
    }
}
