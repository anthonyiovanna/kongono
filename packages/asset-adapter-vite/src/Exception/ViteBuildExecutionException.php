<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Diagnostic\ViteAdapterDiagnostic;
use Throwable;

/** Reports direct-process or output failures with cleanup truth. */
#[Api(ApiStatus::Experimental)]final class ViteBuildExecutionException extends ViteAdapterException
{
    private const array ALLOWED = ['process_start','process_lifecycle','process_nonzero','malformed_result','output_inventory','output_bound','output_changed','output_unsafe','validation_failed','cleanup_failed'];
    public static function failed(string $violation, ?Throwable $previous = null, string $cleanupStatus = 'not_required', bool $output = false): self
    {
        $diagnostic = $output ? ViteAdapterDiagnostic::InvalidOutput : ViteAdapterDiagnostic::ProcessFailure;
        return new self($diagnostic, ['violation' => in_array($violation, self::ALLOWED, true) ? $violation : 'unsupported_violation','cleanup_status' => in_array($cleanupStatus, ['not_required','complete','failed'], true) ? $cleanupStatus : 'failed'], $previous);
    }
}
