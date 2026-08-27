<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\SecurityRuntime\Diagnostic\SecurityRuntimeDiagnostic;

/** Reports one fixed generated-profile violation without disclosing loaded class names. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedSecurityProfileException extends SecurityRuntimeException
{
    private function __construct(string $failureCategory)
    {
        parent::__construct(SecurityRuntimeDiagnostic::InvalidGeneratedProfile, $failureCategory);
    }

    public static function applicationBoundary(): self
    {
        return new self('application_boundary');
    }

    public static function applicationRoot(): self
    {
        return new self('application_root');
    }

    public static function operationRootCount(): self
    {
        return new self('operation_root_count');
    }

    public static function operationRootShape(): self
    {
        return new self('operation_root_shape');
    }

    public static function operationInputs(): self
    {
        return new self('operation_inputs');
    }

    public static function rootAccess(): self
    {
        return new self('root_access');
    }
}
