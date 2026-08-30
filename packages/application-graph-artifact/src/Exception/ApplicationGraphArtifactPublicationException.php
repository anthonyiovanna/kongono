<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Diagnostic\ApplicationGraphArtifactDiagnostic;
use Throwable;

/** Reports guarded no-clobber publication failure. */
#[Api(ApiStatus::Experimental)]
final class ApplicationGraphArtifactPublicationException extends ApplicationGraphArtifactException
{
    public static function failed(string $violation, string $recordKind = 'target', ?Throwable $previous = null): self
    {
        return new self(ApplicationGraphArtifactDiagnostic::PublicationFailed, ['violation' => self::safe($violation), 'operation' => 'publish', 'record_kind' => in_array($recordKind, ['path', 'target', 'temporary', 'artifact'], true) ? $recordKind : 'target'], $previous);
    }

    private static function safe(string $value): string
    {
        return 1 === preg_match('/\A[a-z0-9_]+\z/D', $value) ? $value : 'unsupported_violation';
    }
}
