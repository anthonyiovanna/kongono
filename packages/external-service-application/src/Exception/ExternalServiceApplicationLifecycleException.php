<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Diagnostic\ExternalServiceApplicationDiagnostic;
use Throwable;

/** Reports one-use lifecycle conflicts before another external collaborator call. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceApplicationLifecycleException extends ExternalServiceApplicationException
{
    public static function failed(string $stage, ?Throwable $previous = null): self
    {
        $allowed = ['operation_active', 'application_retired', 'network_application_start'];

        return new self(
            ExternalServiceApplicationDiagnostic::LifecycleFailed,
            ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage'],
            $previous,
        );
    }
}
