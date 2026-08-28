<?php

declare(strict_types=1);

namespace Kongono\Tracing\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Tracing\Diagnostic\TracingDiagnostic;

/** Reports one tracing value violation without retaining rejected evidence. */
#[Api(ApiStatus::Experimental)]
final class InvalidTracingValueException extends TracingException
{
    private function __construct()
    {
        parent::__construct(TracingDiagnostic::Value);
    }

    public static function identifier(): self
    {
        return new self();
    }

    public static function attributes(): self
    {
        return new self();
    }

    public static function timing(): self
    {
        return new self();
    }

    public static function status(): self
    {
        return new self();
    }
}
