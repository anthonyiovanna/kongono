<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Throwable;

/** Marks catchable operational-event failures with package-owned diagnostics. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;

    public function diagnosticContext(): DiagnosticContext;
}
