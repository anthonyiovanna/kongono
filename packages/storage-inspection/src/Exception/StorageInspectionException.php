<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\StorageInspection\Diagnostic\StorageInspectionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic non-disclosing failures and prior causes. */
#[Api(ApiStatus::Experimental)]
abstract class StorageInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly StorageInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidStorageInspectionException
            && !$this instanceof StorageInspectionBindingException
            && !$this instanceof InvalidStorageIntelligenceEvidenceException
            && !$this instanceof StorageInspectionProjectionException
            && !$this instanceof StorageInspectionVerificationException) {
            throw new LogicException('Storage inspection diagnostic and exception type do not match.');
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
        return match ($component) {
            'profile_set_reconstruction', 'artifact_structure', 'current_build_binding',
            'runtime_evidence', 'role_evidence', 'graph_evidence', 'serialization',
            'observation_projection', 'security_audit_projection', 'verification' => $component,
            'runtime_binding' => 'runtime_evidence',
            'graph_role' => 'graph_evidence',
            'observation' => 'observation_projection',
            'security_audit' => 'security_audit_projection',
            default => 'request',
        };
    }
}
