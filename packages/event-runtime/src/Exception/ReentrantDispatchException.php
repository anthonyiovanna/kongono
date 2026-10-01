<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\EventRuntime\Diagnostic\EventRuntimeDiagnostic;
use RuntimeException;

/** Reports an overlapping call before any nested handler is invoked. */
#[Api(ApiStatus::Experimental)]
final class ReentrantDispatchException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    private function __construct()
    {
        $diagnostic = EventRuntimeDiagnostic::ReentrantDispatch;
        $this->diagnosticContext = $diagnostic->context();
        parent::__construct($diagnostic->title() . '.');
    }

    public static function active(): self
    {
        return new self();
    }

    public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }

    public function diagnosticId(): string
    {
        return EventRuntimeDiagnostic::ReentrantDispatch->value;
    }

    public function diagnosticTitle(): string
    {
        return EventRuntimeDiagnostic::ReentrantDispatch->title();
    }

    public function diagnosticRemediation(): string
    {
        return EventRuntimeDiagnostic::ReentrantDispatch->remediation();
    }

    public function __toString(): string
    {
        return sprintf('%s: [%s] %s. Remediation: %s. Context: violation=reentrant_dispatch', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation());
    }
}
