<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use RuntimeException;
use Throwable;

/** Preserves the entry and application-shutdown failures in their original roles. */
#[Api(ApiStatus::Experimental)]
final class CgiEntryShutdownException extends RuntimeException implements ExceptionInterface
{
    private readonly PhpCgiDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;
    private readonly Throwable $primaryFailure;
    private readonly Throwable $shutdownFailure;

    public function __construct(Throwable $primaryFailure, Throwable $shutdownFailure)
    {
        $this->diagnostic = PhpCgiDiagnostic::EntryAndShutdownFailed;
        $this->diagnosticContext = $this->diagnostic->context('entry_and_shutdown_failed');
        $this->primaryFailure = $primaryFailure;
        $this->shutdownFailure = $shutdownFailure;
        parent::__construct($this->diagnostic->title(), 0, $primaryFailure);
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

    public function primaryFailure(): Throwable
    {
        return $this->primaryFailure;
    }

    public function shutdownFailure(): Throwable
    {
        return $this->shutdownFailure;
    }
}
