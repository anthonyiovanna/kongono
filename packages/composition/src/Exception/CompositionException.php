<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Composition\Diagnostic\CompositionDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Owns non-disclosing package diagnostics for every composition failure. */
#[Api(ApiStatus::Internal)]
abstract class CompositionException extends RuntimeException implements ExceptionInterface
{
    private readonly CompositionDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    public function __construct(CompositionDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
    {
        $this->diagnostic = $diagnostic;
        $this->diagnosticContext = $diagnostic->context($violation);
        parent::__construct($diagnostic->title(), 0, $previous);
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

    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
