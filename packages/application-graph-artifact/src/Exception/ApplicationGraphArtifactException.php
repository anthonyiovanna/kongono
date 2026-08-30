<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Diagnostic\ApplicationGraphArtifactDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic nondisclosing failures for Graph artifact operations. */
#[Api(ApiStatus::Internal)]
abstract class ApplicationGraphArtifactException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    final protected function __construct(
        private readonly ApplicationGraphArtifactDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        $valid = match (true) {
            $this instanceof InvalidApplicationGraphArtifactException => in_array($diagnostic, [ApplicationGraphArtifactDiagnostic::InvalidGraphInput, ApplicationGraphArtifactDiagnostic::InvalidArtifact, ApplicationGraphArtifactDiagnostic::ArtifactLimitExceeded], true),
            $this instanceof StaleApplicationGraphArtifactException => ApplicationGraphArtifactDiagnostic::StaleArtifact === $diagnostic,
            $this instanceof ApplicationGraphArtifactPublicationException => ApplicationGraphArtifactDiagnostic::PublicationFailed === $diagnostic,
            $this instanceof ApplicationGraphArtifactLoadException => ApplicationGraphArtifactDiagnostic::LoadFailed === $diagnostic,
            default => false,
        };
        if (!$valid) {
            throw new LogicException('Graph artifact diagnostic and exception type do not match.');
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

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $context));
    }
}
