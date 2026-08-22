<?php

declare(strict_types=1);

namespace Kongono\PhpCgi\Exception;

use InvalidArgumentException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\PhpCgi\Diagnostic\PhpCgiDiagnostic;
use LogicException;
use Throwable;

/** Reports rejected CGI request structure without retaining its raw values. */
#[Api(ApiStatus::Experimental)]
final class InvalidCgiRequestException extends InvalidArgumentException implements ExceptionInterface
{
    private readonly PhpCgiDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    public function __construct(PhpCgiDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
    {
        if (!in_array($diagnostic, [
            PhpCgiDiagnostic::InvalidHostProfile,
            PhpCgiDiagnostic::InvalidServerSnapshot,
            PhpCgiDiagnostic::InvalidRequestMethod,
            PhpCgiDiagnostic::InvalidRequestTarget,
            PhpCgiDiagnostic::InvalidRequestHeaders,
            PhpCgiDiagnostic::InvalidQueryParameters,
            PhpCgiDiagnostic::InvalidCookieParameters,
            PhpCgiDiagnostic::InvalidRequestBody,
            PhpCgiDiagnostic::RequestConstructionFailed,
        ], true)) {
            throw new LogicException('Unsupported PHP CGI invalid-request diagnostic.');
        }

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
}
