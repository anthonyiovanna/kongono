<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceResolverSystem\Diagnostic\SystemResolverDiagnostic;

/** Reports absence of the complete locked native capability set. */
#[Api(ApiStatus::Experimental)]
final class SystemResolverEnvironmentException extends ExternalServiceResolverSystemException
{
    public static function unavailable(): self
    {
        return new self(SystemResolverDiagnostic::RequiredResolverCapabilityUnavailable, [
            'stage' => 'environment',
            'violation' => 'capability_unavailable',
        ]);
    }
}
