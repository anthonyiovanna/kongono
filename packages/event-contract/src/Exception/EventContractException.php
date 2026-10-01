<?php

declare(strict_types=1);

namespace Kongono\EventContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\EventContract\Diagnostic\EventContractDiagnostic;
use LogicException;
use RuntimeException;

/** Provides fixed, nondisclosing presentation for deliberate contract errors. */
#[Api(ApiStatus::Internal)]
abstract class EventContractException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(private readonly EventContractDiagnostic $diagnostic, string $violation)
    {
        if (!$this instanceof InvalidEventContractException) {
            throw new LogicException('Event contract diagnostic and exception type do not match.');
        }
        $this->diagnosticContext = $diagnostic->context($violation);
        parent::__construct($diagnostic->title() . '.');
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
        $fields = $this->diagnosticContext->fields();
        $violation = $fields[0]->value();

        return sprintf('%s: [%s] %s. Remediation: %s. Context: violation=%s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), $violation);
    }
}
