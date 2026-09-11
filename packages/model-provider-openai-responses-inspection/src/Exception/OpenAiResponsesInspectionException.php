<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\ModelProviderOpenAiResponsesInspection\Diagnostic\OpenAiResponsesInspectionDiagnostic;
use LogicException;
use RuntimeException;
use Throwable;

/** Preserves deterministic non-disclosing package failures and prior causes. */
#[Api(ApiStatus::Experimental)]
abstract class OpenAiResponsesInspectionException extends RuntimeException implements ExceptionInterface
{
    private readonly DiagnosticContext $diagnosticContext;

    final protected function __construct(
        private readonly OpenAiResponsesInspectionDiagnostic $diagnostic,
        string $component,
        ?Throwable $previous = null,
    ) {
        if (!$this instanceof InvalidOpenAiResponsesInspectionException
            && !$this instanceof OpenAiResponsesInspectionBindingException
            && !$this instanceof OpenAiResponsesRuntimeEvidenceException
            && !$this instanceof InvalidOpenAiResponsesIntelligenceEvidenceException
            && !$this instanceof OpenAiResponsesInspectionProjectionException) {
            throw new LogicException('OpenAI Responses inspection diagnostic and exception type do not match.');
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
            'request', 'shape', 'digest', 'token', 'flag', 'aggregate', 'serialization', 'runtime_structure', 'semantic_tokens',
            'profile_currentness', 'profile_binding', 'component_binding', 'mapping_surface', 'runtime_binding', 'result_binding',
            'receipt_result', 'event_binding', 'graph_role', 'graph_evidence', 'role_evidence',
            'verification', 'observation', 'security_audit',
        ], true) ? $component : 'unsupported_component';
    }
}
