<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\NetworkEgressPolicyBuild\Diagnostic\NetworkEgressPolicyBuildDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Provides deterministic bounded and non-disclosing package failures. */
#[Api(ApiStatus::Internal)]
abstract class NetworkEgressPolicyBuildException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    /** @param array<string, string|int|bool> $context */
    final protected function __construct(
        private readonly NetworkEgressPolicyBuildDiagnostic $diagnostic,
        array $context,
        ?Throwable $previous = null,
    ) {
        $valid = match (true) {
            $this instanceof InvalidEgressPolicySourceException => in_array($diagnostic, [
                NetworkEgressPolicyBuildDiagnostic::InvalidPolicyIdentity,
                NetworkEgressPolicyBuildDiagnostic::InvalidPolicySource,
                NetworkEgressPolicyBuildDiagnostic::PolicyCompatibilityFailure,
            ], true),
            $this instanceof InvalidEgressPolicyArtifactException => in_array($diagnostic, [
                NetworkEgressPolicyBuildDiagnostic::InvalidPolicyArtifact,
                NetworkEgressPolicyBuildDiagnostic::PolicyBuildLimitExceeded,
            ], true),
            $this instanceof StaleEgressPolicyArtifactException => NetworkEgressPolicyBuildDiagnostic::StalePolicyArtifact === $diagnostic,
            $this instanceof EgressPolicyPublicationException => NetworkEgressPolicyBuildDiagnostic::PublicationFailure === $diagnostic,
            default => false,
        };
        if (!$valid) {
            throw new LogicException('Network egress policy build diagnostic and exception type do not match.');
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
