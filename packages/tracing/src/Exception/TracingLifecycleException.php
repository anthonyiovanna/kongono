<?php

declare(strict_types=1);

namespace Kongono\Tracing\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Tracing\Diagnostic\TracingDiagnostic;

/** Reports one invalid tracing state or ownership transition. */
#[Api(ApiStatus::Experimental)]
final class TracingLifecycleException extends TracingException
{
    private function __construct()
    {
        parent::__construct(TracingDiagnostic::Lifecycle);
    }

    public static function state(): self
    {
        return new self();
    }

    public static function ownership(): self
    {
        return new self();
    }

    public static function capacity(): self
    {
        return new self();
    }
}
