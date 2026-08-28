<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OperationalEvents\Diagnostic\OperationalEventsDiagnostic;

/** Reports fields that do not match an exact operational-event schema. */
#[Api(ApiStatus::Experimental)]
final class InvalidOperationalEventValueException extends OperationalEventException
{
    public static function attributes(): self
    {
        return new self(OperationalEventsDiagnostic::Value);
    }
}
