<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelInvocationBuild\Diagnostic\ModelInvocationBuildDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic non-disclosing invocation-plan build failures. */
#[Api(ApiStatus::Internal)]
abstract class ModelInvocationBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    final protected function __construct(
        private readonly ModelInvocationBuildDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        $valid = match (true) {
            $this instanceof InvalidInvocationPlanSourceException => in_array($diagnostic, [
                ModelInvocationBuildDiagnostic::InvalidPlanIdentity,
                ModelInvocationBuildDiagnostic::InvalidAdapterIdentity,
                ModelInvocationBuildDiagnostic::InvalidExecutionBudget,
                ModelInvocationBuildDiagnostic::InvalidSource,
            ], true),
            $this instanceof InvalidInvocationPlanArtifactException => ModelInvocationBuildDiagnostic::InvalidArtifact === $diagnostic,
            $this instanceof StaleInvocationPlanArtifactException => ModelInvocationBuildDiagnostic::StaleArtifact === $diagnostic,
            $this instanceof InvocationPlanPublicationException => ModelInvocationBuildDiagnostic::PublicationFailed === $diagnostic,
            default => false,
        };
        if (!$valid) {
            throw new LogicException('Model invocation build diagnostic and exception type do not match.');
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
