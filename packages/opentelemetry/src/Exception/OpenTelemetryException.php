<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\OpenTelemetry\Diagnostic\OpenTelemetryDiagnostic;
use RuntimeException;
use Throwable;

/** Supplies eager deterministic fixed OpenTelemetry failure presentation. */
#[Api(ApiStatus::Internal)]
abstract class OpenTelemetryException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(
        private readonly OpenTelemetryDiagnostic $diagnostic,
        ?Throwable $previous = null,
        ?string $signalKind = null,
        ?int $queueCount = null,
        ?int $queueCapacity = null,
        ?string $stage = null,
    ) {
        $this->diagnosticContext = $diagnostic->context($signalKind, $queueCount, $queueCapacity, $stage);
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
