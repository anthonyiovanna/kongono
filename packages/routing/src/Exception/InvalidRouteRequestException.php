<?php

declare(strict_types=1);

namespace Kongono\Routing\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Routing\Diagnostic\RoutingDiagnostic;
use RuntimeException;
use Throwable;

/** Reports invalid request routing input without retaining the request or raw input bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidRouteRequestException extends RuntimeException implements ExceptionInterface
{
    private readonly RoutingDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    public function __construct(RoutingDiagnostic $diagnostic, array $contextValues = [], ?Throwable $previous = null)
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
