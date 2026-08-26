<?php

declare(strict_types=1);

namespace Kongono\PersistenceApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Throwable;

/** Marks deliberate failures owned by persistence-application integration. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticContext(): DiagnosticContext;
    public function diagnosticId(): string;
    public function diagnosticTitle(): string;
    public function diagnosticRemediation(): string;
}
