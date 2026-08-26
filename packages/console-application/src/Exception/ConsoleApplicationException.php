<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use RuntimeException;
use Throwable;

/** Supplies deterministic non-disclosing presentation for application-edge failures. */
#[Api(ApiStatus::Internal)]
abstract class ConsoleApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly ConsoleApplicationDiagnostic $diagnostic,
        ?Throwable $previous = null,
    ) {
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
