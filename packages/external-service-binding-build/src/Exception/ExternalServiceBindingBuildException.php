<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ExternalServiceBindingBuild\Diagnostic\ExternalServiceBindingBuildDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic bounded and non-disclosing package failures. */
#[Api(ApiStatus::Internal)]
abstract class ExternalServiceBindingBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    final protected function __construct(
        private readonly ExternalServiceBindingBuildDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        $valid = match (true) {
            $this instanceof InvalidExternalServiceBindingSourceException => ExternalServiceBindingBuildDiagnostic::InvalidSource === $diagnostic,
            $this instanceof InvalidExternalServiceBindingArtifactException => in_array($diagnostic, [ExternalServiceBindingBuildDiagnostic::InvalidArtifact, ExternalServiceBindingBuildDiagnostic::ArtifactLimitExceeded], true),
            $this instanceof StaleExternalServiceBindingArtifactException => ExternalServiceBindingBuildDiagnostic::StaleArtifact === $diagnostic,
            $this instanceof ExternalServiceBindingArtifactPublicationException => ExternalServiceBindingBuildDiagnostic::ArtifactPublicationFailed === $diagnostic,
            $this instanceof InvalidGeneratedExternalServiceBindingsException => in_array($diagnostic, [ExternalServiceBindingBuildDiagnostic::InvalidGeneratedPhp, ExternalServiceBindingBuildDiagnostic::GeneratedPhpLimitExceeded], true),
            $this instanceof StaleGeneratedExternalServiceBindingsException => ExternalServiceBindingBuildDiagnostic::StaleGeneratedPhp === $diagnostic,
            $this instanceof GeneratedExternalServiceBindingsPublicationException => ExternalServiceBindingBuildDiagnostic::GeneratedPhpPublicationFailed === $diagnostic,
            default => false,
        };
        if (!$valid) {
            throw new LogicException('External-service binding build diagnostic and exception type do not match.');
        }
        $this->diagnosticContext = $diagnostic->context($context);
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

        return sprintf(
            '%s: [%s] %s. Remediation: %s. Context: %s',
            static::class,
            $this->diagnosticId(),
            $this->diagnosticTitle(),
            $this->diagnosticRemediation(),
            implode(', ', $context),
        );
    }
}
