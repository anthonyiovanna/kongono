<?php

declare(strict_types=1);

namespace Kongono\Web\Application\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Web\Application\Diagnostic\WebApplicationDiagnostic;
use RuntimeException;
use Throwable;

/** Provides fixed package-owned diagnostic metadata without presentation behavior. */
#[Api(ApiStatus::Internal)]
abstract class WebApplicationException extends RuntimeException implements ExceptionInterface
{
    private readonly WebApplicationDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    protected function __construct(WebApplicationDiagnostic $diagnostic, ?Throwable $previous = null)
    {
        $this->diagnostic = $diagnostic;
        $this->diagnosticContext = $diagnostic->context();
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
}
