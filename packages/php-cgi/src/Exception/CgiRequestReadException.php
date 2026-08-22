<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use RuntimeException;
use Throwable;

/** Reports bounded input-resource failures without retaining host detail. */
#[Api(ApiStatus::Experimental)]
final class CgiRequestReadException extends RuntimeException implements ExceptionInterface
{
    private readonly PhpCgiDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    public function __construct(string $violation, ?Throwable $previous = null)
    {
        $this->diagnostic = PhpCgiDiagnostic::InputReadFailed;
        $this->diagnosticContext = $this->diagnostic->context($violation);
        parent::__construct($this->diagnostic->title(), 0, $previous);
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
