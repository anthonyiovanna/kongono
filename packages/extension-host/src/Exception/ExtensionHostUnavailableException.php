<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionHost\Diagnostic\ExtensionHostDiagnostic;
use Kongono\ExtensionHost\Runtime\ExtensionHostState;

/** Reports a rejected lifecycle action without exposing operation or extension state. */
#[Api(ApiStatus::Experimental)]
final class ExtensionHostUnavailableException extends ExtensionHostException
{
    public static function unavailable(string $violation, ExtensionHostState $state): self
    {
        $violation = in_array($violation, ['host_not_active', 'active_operations', 'operation_lease_missing'], true)
            ? $violation
            : 'host_not_active';

        return new self(ExtensionHostDiagnostic::HostUnavailable, ['violation' => $violation, 'state' => $state->value]);
    }
}
