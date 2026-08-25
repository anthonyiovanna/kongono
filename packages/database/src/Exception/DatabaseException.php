<?php

declare(strict_types=1);

namespace Kongono\Database\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Diagnostic\DatabaseDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Supplies deterministic non-disclosing presentation for operational failures. */
#[Api(ApiStatus::Internal)]
abstract class DatabaseException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(private readonly DatabaseDiagnostic $diagnostic, ?Throwable $previous = null)
    {
        $this->diagnosticContext = $diagnostic->context();
        parent::__construct($diagnostic->title(), 0, $previous);
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

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
