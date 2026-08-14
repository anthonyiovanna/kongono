<?php

declare(strict_types=1);

namespace Kongono\Kernel\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Throwable;

/**
 * Marks failures deliberately raised by the Kernel package.
 *
 * Consumers may catch this Public boundary without depending on an Experimental
 * concrete exception. Native engine errors are not promised to implement it.
 */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticContext(): DiagnosticContext;

    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;
}
