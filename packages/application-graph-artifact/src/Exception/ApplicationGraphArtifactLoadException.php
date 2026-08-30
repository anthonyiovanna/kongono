<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Diagnostic\ApplicationGraphArtifactDiagnostic;
use Throwable;

/** Reports guarded explicit artifact loading failure. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphArtifactLoadException extends ApplicationGraphArtifactException
{
    public static function failed(string $violation, string $recordKind = 'path', ?Throwable $previous = null): self
    {
        return new self(ApplicationGraphArtifactDiagnostic::LoadFailed, ['violation' => self::safe($violation), 'operation' => 'load', 'record_kind' => in_array($recordKind, ['path', 'artifact'], true) ? $recordKind : 'path'], $previous);
    }

    private static function safe(string $value): string
    {
        return 1 === preg_match('/\A[a-z0-9_]+\z/D', $value) ? $value : 'unsupported_violation';
    }
}
