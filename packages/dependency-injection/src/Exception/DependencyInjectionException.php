<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/**
 * Provides the Internal base for deliberate dependency-resolution failures.
 *
 * The base defines no transport, logging, retry, serialization, or rendering
 * policy. Consumers should catch the package's Public marker when appropriate.
 */
#[Api(ApiStatus::Internal)]
abstract class DependencyInjectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DependencyInjectionDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final public function __construct(
        DependencyInjectionDiagnostic $diagnostic,
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
