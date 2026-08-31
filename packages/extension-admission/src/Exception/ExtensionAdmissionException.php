<?php

declare(strict_types=1);

namespace Kongono\ExtensionAdmission\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ExtensionAdmission\Diagnostic\ExtensionAdmissionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides fixed bounded failures without disclosing rejected Graph values or source bytes. */
#[Api(ApiStatus::Experimental)]
abstract class ExtensionAdmissionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final protected function __construct(array $contextValues, ?Throwable $previous = null)
    {
        if (!$this instanceof InvalidExtensionAdmissionException && !$this instanceof UnresolvedExtensionConflictException) {
            throw new LogicException('Extension admission exception type is unsupported.');
        }

        $diagnostic = ExtensionAdmissionDiagnostic::AdmissionFailed;
        $this->diagnosticContext = $diagnostic->context($contextValues);
        parent::__construct($diagnostic->title() . '.', 0, $previous);
    }

    final public function diagnosticContext(): DiagnosticContext
    {
        return $this->diagnosticContext;
    }
    final public function diagnosticId(): string
    {
        return ExtensionAdmissionDiagnostic::AdmissionFailed->value;
    }
    final public function diagnosticTitle(): string
    {
        return ExtensionAdmissionDiagnostic::AdmissionFailed->title();
    }
    final public function diagnosticRemediation(): string
    {
        return ExtensionAdmissionDiagnostic::AdmissionFailed->remediation();
    }

    final public function __toString(): string
    {
        $context = [];
        foreach ($this->diagnosticContext->fields() as $field) {
            $context[] = $field->key() . '=' . $field->value();
        }

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $context));
    }
}
