<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Diagnostic\ViewApplicationDiagnostic;

/** Reports application-owned pre-render operation rejection. */
#[Api(ApiStatus::Internal)]
final class ViewApplicationOperationException extends ViewApplicationException
{
    public static function mismatch(): self
    {
        return new self(ViewApplicationDiagnostic::OperationFailed, 'compiled_identity_mismatch');
    }
}
