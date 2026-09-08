<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\NetworkEgressInspection\Diagnostic\NetworkEgressInspectionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic nondisclosing package failures and prior causes. */
#[Api(ApiStatus::Internal)]
abstract class NetworkEgressInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly NetworkEgressInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidNetworkEgressInspectionException
            && !$this instanceof NetworkEgressInspectionBindingException
            && !$this instanceof NetworkEgressRuntimeEvidenceException
            && !$this instanceof InvalidNetworkEgressIntelligenceEvidenceException
            && !$this instanceof NetworkEgressInspectionProjectionException) {
            throw new LogicException('Network-egress inspection diagnostic and exception type do not match.');
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
            'request', 'digest', 'aggregate', 'serialization', 'runtime_structure', 'semantic_tokens',
            'profile_wiring', 'profile_binding', 'artifact_binding', 'runtime_binding', 'result_binding',
            'receipt_result', 'graph_role', 'graph_evidence', 'role_evidence', 'verification',
            'observation', 'security_audit',
        ], true) ? $component : 'unsupported_component';
    }
}
