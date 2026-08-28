<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OperationalEvents\Diagnostic\OperationalEventsDiagnostic;

/** Reports invalid bounded field and schema declarations. */
#[Api(ApiStatus::Experimental)]
final class InvalidOperationalEventSchemaException extends OperationalEventException
{
    public static function field(): self
    {
        return new self(OperationalEventsDiagnostic::Schema);
    }

    public static function schema(): self
    {
        return new self(OperationalEventsDiagnostic::Schema);
    }
}
