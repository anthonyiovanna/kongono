<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;

/** Marks failures owned by the Security Policy Build package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends \Throwable
{
    public function diagnosticContext(): DiagnosticContext;

    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;
}
