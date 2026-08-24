<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use Throwable;

/** Reports a fixed, non-disclosing generated web-profile violation. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedWebRuntimeException extends WebRuntimeException
{
    /** @var list<string> */
    private const VIOLATIONS = [
        'application_retired',
        'application_root_count',
        'application_root_shape',
        'operation_root_present',
        'root_access_failed',
    ];

    public function __construct(string $violation, ?Throwable $previous = null)
    {
        parent::__construct(
            WebRuntimeDiagnostic::InvalidGeneratedIntegration,
            $previous,
            in_array($violation, self::VIOLATIONS, true) ? $violation : 'application_root_shape',
        );
    }
}
