<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\AgentInspection\Diagnostic\AgentInspectionDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic non-disclosing package failures and prior causes. */
#[Api(ApiStatus::Internal)]
abstract class AgentInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly AgentInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidAgentInspectionException
            && !$this instanceof AgentInspectionBindingException
            && !$this instanceof InvalidCompletedRuntimeEvidenceException
            && !$this instanceof InvalidAgentIntelligenceEvidenceException
            && !$this instanceof AgentInspectionProjectionException) {
            throw new LogicException('Agent inspection diagnostic and exception type do not match.');
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

        return sprintf('%s: [%s] %s. Remediation: %s. Context: %s', static::class, $this->diagnosticId(), $this->diagnosticTitle(), $this->diagnosticRemediation(), implode(', ', $fields));
    }

    final protected static function safe(string $component): string
    {
        return in_array($component, [
            'request', 'identity', 'digest', 'count', 'serialization', 'semantic_tokens',
            'profile_declaration', 'profile_artifact', 'profile_plan', 'profile_wiring',
            'capability_order', 'constraints', 'evidence_references', 'runtime_structure',
            'receipt_principal', 'graph_role', 'graph_evidence', 'role_evidence',
            'verification', 'observation', 'security_audit',
        ], true) ? $component : 'unsupported_component';
    }
}
