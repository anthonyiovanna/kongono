<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ExternalServiceInspection\Diagnostic\ExternalServiceInspectionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic nondisclosing package failures and prior causes. */
#[Api(ApiStatus::Internal)]
abstract class ExternalServiceInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly ExternalServiceInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidExternalServiceInspectionException
            && !$this instanceof ExternalServiceInspectionBindingException
            && !$this instanceof ExternalServiceResultEvidenceException
            && !$this instanceof InvalidExternalServiceIntelligenceEvidenceException
            && !$this instanceof ExternalServiceInspectionProjectionException) {
            throw new LogicException('External-service inspection diagnostic and exception type do not match.');
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
            'request', 'digest', 'aggregate', 'serialization', 'result_structure', 'semantic_tokens',
            'profile_binding', 'artifact_binding', 'selected_binding', 'network_profile', 'policy_binding',
            'result_binding', 'credential_result', 'receipt_result', 'runtime_bounds',
            'graph_role', 'graph_evidence', 'role_evidence', 'verification',
            'observation', 'security_audit',
        ], true) ? $component : 'unsupported_component';
    }
}
