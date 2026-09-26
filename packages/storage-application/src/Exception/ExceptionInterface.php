<?php

declare(strict_types=1);

namespace Kongono\StorageApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Stringable;
use Throwable;

/** Marks all failures owned by the Storage Application package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable, Stringable
{
    public function diagnosticId(): string;
    public function diagnosticTitle(): string;
    public function diagnosticRemediation(): string;
    public function diagnosticContext(): DiagnosticContext;
}
