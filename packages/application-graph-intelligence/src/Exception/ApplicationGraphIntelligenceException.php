<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Diagnostic\ApplicationGraphIntelligenceDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic non-disclosing failures for the intelligence package. */
#[Api(ApiStatus::Internal)]
abstract class ApplicationGraphIntelligenceException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly ApplicationGraphIntelligenceDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidApplicationGraphIntelligenceValueException) {
            throw new LogicException('Application Graph intelligence diagnostic and exception type do not match.');
        }

        $this->diagnosticContext = $diagnostic->context($component);
        parent::__construct($diagnostic->title() . '.', 0, $previous);
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
        $fields = [];
        foreach ($this->diagnosticContext->fields() as $field) {
            $fields[] = $field->key() . '=' . $field->value();
        }

        return sprintf(
            '%s: [%s] %s. Remediation: %s. Context: %s',
            static::class,
            $this->diagnosticId(),
            $this->diagnosticTitle(),
            $this->diagnosticRemediation(),
            implode(', ', $fields),
        );
    }
}
