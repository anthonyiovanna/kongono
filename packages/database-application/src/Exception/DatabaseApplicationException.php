<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseApplication\Diagnostic\DatabaseApplicationDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Owns fixed non-disclosing presentation for integration failures. */
#[Api(ApiStatus::Internal)]
abstract class DatabaseApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly DatabaseApplicationDiagnostic $diagnostic,
        string $failureCategory,
        ?Throwable $previous = null,
    ) {
        $this->diagnosticContext = $diagnostic->context($failureCategory);
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
