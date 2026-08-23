<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use RuntimeException;
use Throwable;

/** Provides fixed package-owned diagnostics without rendering rejected values. */
#[Api(ApiStatus::Internal)]
abstract class KnowledgeException extends RuntimeException implements ExceptionInterface
{
    private readonly KnowledgeDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    final public function __construct(KnowledgeDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
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
