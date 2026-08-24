<?php

declare(strict_types=1);

namespace Kongono\Web\Runtime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic;
use RuntimeException;
use Throwable;

/** Provides fixed package-owned diagnostic metadata without transport data. */
#[Api(ApiStatus::Internal)]
abstract class WebRuntimeException extends RuntimeException implements ExceptionInterface
{
    private readonly WebRuntimeDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(WebRuntimeDiagnostic $diagnostic, ?Throwable $previous = null, ?string $violation = null)
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
