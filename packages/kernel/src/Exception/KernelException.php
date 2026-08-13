<?php

declare(strict_types=1);

namespace Kongono\Kernel\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use RuntimeException;
use Throwable;

/**
 * Provides the Internal runtime-exception base for deliberate Kernel failures.
 *
 * Consumers should catch {@see ExceptionInterface}; this base is not an extension
 * point and has no logging, rendering, transport, telemetry, or retry semantics.
 */
#[Api(ApiStatus::Internal)]
abstract class KernelException extends RuntimeException implements ExceptionInterface
{
    final public function __construct(
        private readonly LifecycleDiagnostic $diagnostic,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    final public function diagnosticId(): string
    {
        return $this->diagnostic->value;
    }

    final public function diagnosticTitle(): string
    {
        return $this->diagnostic->title();
    }

    final public function diagnosticRemediation(): string
    {
        return $this->diagnostic->remediation();
    }
}
