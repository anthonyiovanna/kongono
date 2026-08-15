<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use RuntimeException;
use Throwable;

/** Provides the context-free Internal base for projection construction failures. */
#[Api(ApiStatus::Internal)]
abstract class DiagnosticPresentationException extends RuntimeException implements ExceptionInterface
{
    final public function __construct(
        private readonly DiagnosticPresentationDiagnostic $diagnostic,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
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
