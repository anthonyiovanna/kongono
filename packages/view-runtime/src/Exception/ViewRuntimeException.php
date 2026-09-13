<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic non-disclosing failures for the compiled runtime boundary. */
#[Api(ApiStatus::Internal)]
abstract class ViewRuntimeException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $contextValues */
    final protected function __construct(private readonly ViewRuntimeDiagnostic $diagnostic, array $contextValues, ?Throwable $previous = null)
    {
        $expected = match ($diagnostic) {
            ViewRuntimeDiagnostic::InvalidArtifact => InvalidRuntimeArtifactException::class,
            ViewRuntimeDiagnostic::StaleArtifact => StaleRuntimeArtifactException::class,
            ViewRuntimeDiagnostic::ArtifactLoadFailed => ArtifactLoadException::class,
            ViewRuntimeDiagnostic::InvalidRequest => InvalidRenderRequestException::class,
            ViewRuntimeDiagnostic::UnsupportedOutputContext, ViewRuntimeDiagnostic::RenderingFailed => ViewRenderingException::class,
            ViewRuntimeDiagnostic::BoundExceeded => RenderBoundExceededException::class,
            ViewRuntimeDiagnostic::RetirementFailed => RenderRetirementException::class,
        };
        if (!$this instanceof $expected) {
            throw new LogicException('View runtime diagnostic and exception type do not match.');
        }
        $this->diagnosticContext = $diagnostic->context($contextValues);
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
        $context = [];
        foreach ($this->diagnosticContext->fields() as $field) {
            $context[] = $field->key() . '=' . $field->value();
        }

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $context));
    }
}
