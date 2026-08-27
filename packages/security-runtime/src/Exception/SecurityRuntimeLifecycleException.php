<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityRuntime\Diagnostic\SecurityRuntimeDiagnostic;

/** Reports use of a retired application or closed operation. */
#[Api(ApiStatus::Experimental)]
final class SecurityRuntimeLifecycleException extends SecurityRuntimeException
{
    private function __construct(string $failureCategory)
    {
        parent::__construct(SecurityRuntimeDiagnostic::LifecycleUnavailable, $failureCategory);
    }

    public static function applicationRetired(): self
    {
        return new self('application_retired');
    }

    public static function operationClosed(): self
    {
        return new self('operation_closed');
    }
}
