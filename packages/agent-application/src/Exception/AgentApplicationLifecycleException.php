<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports an honest Open/Closed/active lifecycle conflict. */
#[Api(ApiStatus::Experimental)]
final class AgentApplicationLifecycleException extends AgentApplicationException
{
    public static function failed(string $stage): self
    {
        $allowed = ['application_closed', 'operation_active', 'active_close'];

        return new self(AgentApplicationDiagnostic::LifecycleFailed, ['stage' => in_array($stage, $allowed, true) ? $stage : 'unsupported_stage']);
    }
}
