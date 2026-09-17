<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheInspection\Diagnostic\CacheInspectionDiagnostic;
use Kongono\Diagnostics\Context\DiagnosticContext;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic non-disclosing failures and prior causes. */
#[Api(ApiStatus::Experimental)]
abstract class CacheInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly CacheInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidCacheInspectionException
            && !$this instanceof CacheInspectionBindingException
            && !$this instanceof InvalidCacheIntelligenceEvidenceException
            && !$this instanceof CacheInspectionProjectionException
            && !$this instanceof CacheInspectionVerificationException) {
            throw new LogicException('Cache inspection diagnostic and exception type do not match.');
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
            'request', 'shape', 'digest', 'count', 'total', 'flag', 'token', 'limitation', 'serialization',
            'profile_set_reconstruction', 'artifact_structure', 'artifact_comparison', 'current_build_binding',
            'runtime_evidence', 'runtime_binding', 'graph_role', 'graph_evidence', 'role_evidence',
            'observation', 'security_audit', 'verification',
        ], true) ? $component : 'unsupported_component';
    }
}
