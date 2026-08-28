<?php

declare(strict_types=1);

namespace Kongono\Observability\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Diagnostic\ObservabilityDiagnostic;

/** Reports one neutral contract violation without retaining rejected input. */
#[Api(ApiStatus::Experimental)]
final class InvalidObservabilityValueException extends ObservabilityException
{
    private function __construct(ObservabilityDiagnostic $diagnostic)
    {
        parent::__construct($diagnostic);
    }

    public static function context(): self
    {
        return new self(ObservabilityDiagnostic::Context);
    }

    public static function attribute(): self
    {
        return new self(ObservabilityDiagnostic::Attribute);
    }

    public static function signalIdentity(): self
    {
        return new self(ObservabilityDiagnostic::SignalIdentity);
    }
}
