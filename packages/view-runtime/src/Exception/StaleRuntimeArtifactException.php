<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;

/** Reports caller-selected currentness mismatch without path or artifact disclosure. */
#[Api(ApiStatus::Experimental)]
final class StaleRuntimeArtifactException extends ViewRuntimeException
{
    public static function mismatch(string $violation = 'compiled_identity_mismatch', ?string $sha256 = null): self
    {
        $context = ['violation' => in_array($violation, ['compiled_identity_mismatch', 'directory_identity_mismatch'], true) ? $violation : 'unsupported_violation'];
        if (null !== $sha256 && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $sha256)) {
            $context['sha256'] = $sha256;
        }

        return new self(ViewRuntimeDiagnostic::StaleArtifact, $context);
    }
}
