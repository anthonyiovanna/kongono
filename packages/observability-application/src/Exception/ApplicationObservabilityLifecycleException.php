<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ObservabilityApplication\Diagnostic\ObservabilityApplicationDiagnostic;

/** Reports one fixed invalid application or operation lifecycle transition. */
#[Api(ApiStatus::Experimental)]
final class ApplicationObservabilityLifecycleException extends ObservabilityApplicationException
{
    private function __construct(string $stage)
    {
        $diagnostic = ObservabilityApplicationDiagnostic::Lifecycle;
        parent::__construct($diagnostic, $diagnostic->lifecycleContext($stage));
    }

    public static function applicationClosed(): self
    {
        return new self('application_closed');
    }

    public static function operationCapacity(): self
    {
        return new self('operation_capacity');
    }

    public static function activeOperations(): self
    {
        return new self('active_operations');
    }

    public static function operationState(): self
    {
        return new self('operation_state');
    }

    public static function operationContext(): self
    {
        return new self('operation_context');
    }
}
