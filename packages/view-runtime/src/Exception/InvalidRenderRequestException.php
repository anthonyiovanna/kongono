<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use Throwable;

/** Reports an inconsistent request-to-artifact relation. */
#[Api(ApiStatus::Experimental)]
final class InvalidRenderRequestException extends ViewRuntimeException
{
    private const array VIOLATIONS = ['contract_version', 'compiled_identity', 'view_identity', 'context_schema', 'context_value', 'operation_state', 'sink_protocol'];

    public static function invalid(string $violation, ?string $viewIdentity = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation'];
        if (null !== $viewIdentity && 1 === preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $viewIdentity)) {
            $context['view_identity'] = $viewIdentity;
        }

        return new self(ViewRuntimeDiagnostic::InvalidRequest, $context, $previous);
    }
}
