<?php

declare(strict_types=1);

namespace Kongono\Kernel\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
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
    private readonly LifecycleDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final public function __construct(
        LifecycleDiagnostic $diagnostic,
        string $message,
        array $contextValues,
        ?Throwable $previous = null,
    ) {
        $this->diagnostic = $diagnostic;
        $this->diagnosticContext = $diagnostic->context($contextValues);
        parent::__construct($message, 0, $previous);
    }

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
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
