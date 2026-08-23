<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Throwable;

/** Marks deliberate presentation-neutral framework-knowledge failures. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticContext(): DiagnosticContext;

    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;
}
