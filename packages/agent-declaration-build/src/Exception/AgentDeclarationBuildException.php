<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Exception;

use Kongono\AgentDeclarationBuild\Diagnostic\AgentDeclarationBuildDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic non-disclosing declaration artifact failures. */
#[Api(ApiStatus::Internal)]
abstract class AgentDeclarationBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    final protected function __construct(
        private readonly AgentDeclarationBuildDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        $valid = match (true) {
            $this instanceof InvalidAgentDeclarationException => in_array($diagnostic, [AgentDeclarationBuildDiagnostic::InvalidDeclaration, AgentDeclarationBuildDiagnostic::BuildLimitExceeded], true),
            $this instanceof InvalidAgentDeclarationArtifactException => in_array($diagnostic, [AgentDeclarationBuildDiagnostic::InvalidArtifact, AgentDeclarationBuildDiagnostic::BuildLimitExceeded], true),
            $this instanceof StaleAgentDeclarationArtifactException => AgentDeclarationBuildDiagnostic::StaleArtifact === $diagnostic,
            $this instanceof AgentDeclarationPublicationException => AgentDeclarationBuildDiagnostic::PublicationFailed === $diagnostic,
            default => false,
        };
        if (!$valid) {
            throw new LogicException('Agent declaration build diagnostic and exception type do not match.');
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
