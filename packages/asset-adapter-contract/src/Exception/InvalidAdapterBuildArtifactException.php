<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Diagnostic\AssetAdapterContractDiagnostic;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;
use Throwable;

/** Reports invalid, incomplete, or stale canonical adapter artifacts. */
#[Api(ApiStatus::Experimental)]
final class InvalidAdapterBuildArtifactException extends AssetAdapterContractException
{
    private const array VIOLATIONS = ['artifact_framing', 'artifact_canonical', 'artifact_schema', 'artifact_currentness', 'artifact_identity_relation', 'output_map_shape', 'output_map_relation', 'output_bytes_relation'];
    public static function invalid(string $violation, ?Throwable $previous = null, ?string $evidenceSha256 = null): self
    {
        $context = ['violation' => AdapterContractGuard::token($violation, self::VIOLATIONS)];
        if (null !== $evidenceSha256 && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $evidenceSha256)) {
            $context['evidence_sha256'] = $evidenceSha256;
        }
        return new self(AssetAdapterContractDiagnostic::InvalidArtifact, $context, $previous);
    }
}
