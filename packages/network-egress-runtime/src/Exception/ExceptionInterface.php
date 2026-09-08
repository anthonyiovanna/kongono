<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Throwable;

/** Marks every bounded exception exposed by the network-egress runtime. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticContext(): DiagnosticContext;
    public function diagnosticId(): string;
    public function diagnosticTitle(): string;
    public function diagnosticRemediation(): string;
}
