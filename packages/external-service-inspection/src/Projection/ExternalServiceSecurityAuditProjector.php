<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceInspectionProjectionException;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
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

/** Constructs one fixed Safe audit value without recording, export, or compliance claims. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceSecurityAuditProjector
{
    public function project(ExternalServiceInspection $inspection, ObservabilityContext $context): SecurityAuditEvidence
    {
        try {
            $attributes = new AttributeSet([
                $this->text('inspection.schema', ExternalServiceInspection::SCHEMA),
                $this->integer('inspection.schema_version', ExternalServiceInspection::VERSION),
                $this->boolean('inspection.completed_result_supplied', $inspection->resultSupplied()),
                $this->boolean('inspection.response_present', $inspection->responsePresent()),
                $this->boolean('inspection.failure_present', $inspection->failurePresent()),
                $this->boolean('inspection.lease_cleanup_complete', $inspection->credentialCleanupComplete()),
                $this->boolean('inspection.possible_effect_uncertainty', $inspection->possibleEffectUncertainty()),
                $this->text('inspection.scope', $inspection->scope()),
                $this->text('inspection.disclosure', $inspection->disclosure()),
                $this->text('inspection.authority', $inspection->authority()),
                $this->text('inspection.projection', 'constructed-not-recorded'),
            ]);

            return new SecurityAuditEvidence($context, $this->definition(), SecurityAuditOutcome::Succeeded, $attributes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExternalServiceInspectionProjectionException) {
                throw $failure;
            }
            throw ExternalServiceInspectionProjectionException::failed('security_audit', $failure);
        }
    }

    private function definition(): SecurityAuditDefinition
    {
        return new SecurityAuditDefinition(
            new SignalIdentity('kongono.external-service.inspection.security', 1),
            SecurityAuditRetentionIntent::Standard,
            new SecurityAuditSchema([
                $this->field('inspection.schema', AttributeValueKind::Text, [AttributeValue::text(ExternalServiceInspection::SCHEMA)]),
                $this->field('inspection.schema_version', AttributeValueKind::Integer, [AttributeValue::integer(1)]),
                $this->field('inspection.completed_result_supplied', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.response_present', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.failure_present', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.lease_cleanup_complete', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.possible_effect_uncertainty', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.scope', AttributeValueKind::Text, [AttributeValue::text(ExternalServiceInspection::SCOPE)]),
                $this->field('inspection.disclosure', AttributeValueKind::Text, [AttributeValue::text(ExternalServiceInspection::DISCLOSURE)]),
                $this->field('inspection.authority', AttributeValueKind::Text, [AttributeValue::text(ExternalServiceInspection::AUTHORITY)]),
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
