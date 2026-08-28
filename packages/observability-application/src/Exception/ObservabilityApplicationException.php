<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ObservabilityApplication\Diagnostic\ObservabilityApplicationDiagnostic;
use RuntimeException;
use Throwable;

/** Supplies eager deterministic application observability failure presentation. */
#[Api(ApiStatus::Internal)]
abstract class ObservabilityApplicationException extends RuntimeException implements ExceptionInterface
{
    protected function __construct(
        private readonly ObservabilityApplicationDiagnostic $diagnostic,
        private readonly DiagnosticContext $context,
        ?Throwable $previous = null,
    ) {
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
        return $this->context;
    }

    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
