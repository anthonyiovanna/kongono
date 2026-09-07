<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ModelInvocationInspection\Exception\ModelInvocationInspectionProjectionException;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use Throwable;

/** Constructs one fixed Safe audit value without recording or compliance claims. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationSecurityAuditProjector
{
    public function project(ModelInvocationInspection $inspection, ObservabilityContext $context): SecurityAuditEvidence
    {
        try {
            $attributes = new AttributeSet([
                $this->text('inspection.schema', ModelInvocationInspection::SCHEMA),
                $this->integer('inspection.schema_version', ModelInvocationInspection::VERSION),
                $this->boolean('inspection.runtime_evidence_supplied', $inspection->runtimeEvidenceSupplied()),
                $this->boolean('inspection.result_present', $inspection->resultPresent()),
                $this->boolean('inspection.cost_present', $inspection->costPresent()),
                $this->boolean('inspection.cleanup_complete', $inspection->cleanupComplete()),
                $this->text('inspection.scope', $inspection->scope()),
                $this->text('inspection.disclosure', $inspection->disclosure()),
                $this->text('inspection.authority', $inspection->authority()),
                $this->text('inspection.projection', 'constructed-not-recorded'),
            ]);

            return new SecurityAuditEvidence($context, $this->definition(), SecurityAuditOutcome::Succeeded, $attributes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ModelInvocationInspectionProjectionException) {
                throw $failure;
            }
            throw ModelInvocationInspectionProjectionException::failed('security_audit', $failure);
        }
    }

    private function definition(): SecurityAuditDefinition
    {
        return new SecurityAuditDefinition(
            new SignalIdentity('kongono.model-invocation.inspection.security', 1),
            SecurityAuditRetentionIntent::Standard,
            new SecurityAuditSchema([
                $this->field('inspection.schema', AttributeValueKind::Text, [AttributeValue::text(ModelInvocationInspection::SCHEMA)]),
                $this->field('inspection.schema_version', AttributeValueKind::Integer, [AttributeValue::integer(1)]),
                $this->field('inspection.runtime_evidence_supplied', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.result_present', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.cost_present', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.cleanup_complete', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.scope', AttributeValueKind::Text, [AttributeValue::text(ModelInvocationInspection::SCOPE)]),
                $this->field('inspection.disclosure', AttributeValueKind::Text, [AttributeValue::text(ModelInvocationInspection::DISCLOSURE)]),
                $this->field('inspection.authority', AttributeValueKind::Text, [AttributeValue::text(ModelInvocationInspection::AUTHORITY)]),
                $this->field('inspection.projection', AttributeValueKind::Text, [AttributeValue::text('constructed-not-recorded')]),
            ]),
        );
    }

    /** @param list<AttributeValue> $allowed */
    private function field(string $key, AttributeValueKind $kind, array $allowed): SecurityAuditField
    {
        return new SecurityAuditField(new AttributeKey($key), $kind, DiagnosticDisclosure::Safe, true, $allowed);
    }

    private function integer(string $key, int $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::integer($value));
    }

    private function boolean(string $key, bool $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::boolean($value));
    }

    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }
}
