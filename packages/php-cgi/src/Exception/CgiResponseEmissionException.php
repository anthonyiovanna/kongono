<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Reports package-owned preflight or committed emission failure. */
#[Api(ApiStatus::Experimental)]
final class CgiResponseEmissionException extends RuntimeException implements ExceptionInterface
{
    private readonly PhpCgiDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;
    private readonly bool $commitAttempted;

    public function __construct(
        PhpCgiDiagnostic $diagnostic,
        string $violation,
        bool $commitAttempted,
        ?Throwable $previous = null,
    ) {
        if (
            (PhpCgiDiagnostic::ResponsePreflightFailed !== $diagnostic || $commitAttempted)
            && (PhpCgiDiagnostic::ResponseEmissionFailed !== $diagnostic || !$commitAttempted)
        ) {
            throw new LogicException('Unsupported PHP CGI response-emission diagnostic state.');
        }

        $this->diagnostic = $diagnostic;
        $this->diagnosticContext = $diagnostic->context($violation);
        $this->commitAttempted = $commitAttempted;
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

    public function commitAttempted(): bool
    {
        return $this->commitAttempted;
    }
}
