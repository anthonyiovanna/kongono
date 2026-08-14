<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use RuntimeException;
use Throwable;

/**
 * Provides the Internal base for deliberate Diagnostics construction failures.
 *
 * Consumers should catch the package marker. This base performs no rendering,
 * logging, serialization, transport, retry, or context construction.
 */
#[Api(ApiStatus::Internal)]
abstract class DiagnosticsException extends RuntimeException implements ExceptionInterface
{
    final public function __construct(
        private readonly DiagnosticsDiagnostic $diagnostic,
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
