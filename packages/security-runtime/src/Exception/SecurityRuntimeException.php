<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\SecurityRuntime\Diagnostic\SecurityRuntimeDiagnostic;
use RuntimeException;

/** Owns fixed non-disclosing presentation for Security Runtime failures. */
#[Api(ApiStatus::Internal)]
abstract class SecurityRuntimeException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly SecurityRuntimeDiagnostic $diagnostic,
        string $failureCategory,
    ) {
        $this->diagnosticContext = $diagnostic->context($failureCategory);
        parent::__construct($diagnostic->title());
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
