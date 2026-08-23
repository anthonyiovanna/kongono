<?php

declare(strict_types=1);

namespace Kongono\Hantei\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Hantei\Diagnostic\HanteiDiagnostic;
use RuntimeException;
use Throwable;

/** Provides fixed Hantei diagnostics while retaining native causes internally. */
#[Api(ApiStatus::Internal)]
abstract class HanteiException extends RuntimeException implements ExceptionInterface
{
    private readonly HanteiDiagnostic $diagnostic;
    private readonly DiagnosticContext $diagnosticContext;

    final public function __construct(HanteiDiagnostic $diagnostic, string $violation, ?Throwable $previous = null)
    {
        if (!$this instanceof InvalidHanteiRequestException || !in_array($diagnostic, HanteiDiagnostic::cases(), true)) {
            throw InvalidHanteiValueException::exceptionDiagnostic();
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

    final public function __toString(): string
    {
        return sprintf('%s: %s', $this->diagnostic->value, $this->diagnostic->title());
    }
}
