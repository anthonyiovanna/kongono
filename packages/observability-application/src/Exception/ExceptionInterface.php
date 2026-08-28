<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Throwable;

/** Marks every exception owned by the observability application package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;

    public function diagnosticContext(): DiagnosticContext;
}
