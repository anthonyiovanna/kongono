<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ViewInspection\Diagnostic\ViewInspectionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic non-disclosing inspection failures and prior causes. */
#[Api(ApiStatus::Experimental)]
abstract class ViewInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly ViewInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidViewInspectionException
            && !$this instanceof ViewArtifactEvidenceException
            && !$this instanceof ViewInspectionBindingException
            && !$this instanceof InvalidViewIntelligenceEvidenceException
            && !$this instanceof ViewInspectionProjectionException) {
            throw new LogicException('Compiled-view inspection diagnostic and exception type do not match.');
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
            'request', 'shape', 'digest', 'token', 'flag', 'aggregate', 'format_version', 'limitation', 'serialization',
            'artifact_structure', 'artifact_comparison', 'profile_identity', 'current_build_binding',
            'graph_role', 'graph_evidence', 'role_evidence', 'verification', 'observation', 'security_audit',
        ], true) ? $component : 'unsupported_component';
    }
}
