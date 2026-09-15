<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterVite\Diagnostic\ViteAdapterDiagnostic;
use Throwable;

/** Reports contained workspace creation, writing, or cleanup failures. */
#[Api(ApiStatus::Experimental)]final class ViteWorkspaceException extends ViteAdapterException
{
    private const array ALLOWED = ['parent_invalid','operation_collision','path_unsafe','create_failed','write_failed','source_changed','cleanup_failed','residue'];
    public static function failed(string $violation, ?Throwable $previous = null, string $cleanupStatus = 'not_required'): self
    {
        return new self(ViteAdapterDiagnostic::WorkspaceFailure, ['violation' => in_array($violation, self::ALLOWED, true) ? $violation : 'unsupported_violation','cleanup_status' => in_array($cleanupStatus, ['not_required','complete','failed'], true) ? $cleanupStatus : 'failed'], $previous);
    }
}
