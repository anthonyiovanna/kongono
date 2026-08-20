<?php

declare(strict_types=1);

namespace Kongono\Http\Server\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Http\Server\Diagnostic\HttpServerDiagnostic;
use Throwable;

/** Reports invalid HTTP Server composition without retaining caller-owned values. */
#[Api(ApiStatus::Experimental)]
final class InvalidHttpServerDefinitionException extends InvalidArgumentException implements ExceptionInterface
{
    private readonly HttpServerDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    public function __construct(HttpServerDiagnostic $diagnostic, array $contextValues = [], ?Throwable $previous = null)
    {
        $this->diagnostic = $diagnostic;
        $this->diagnosticContext = $diagnostic->context($contextValues);
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
