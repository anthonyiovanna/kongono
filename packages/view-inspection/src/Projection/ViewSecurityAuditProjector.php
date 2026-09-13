<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
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
use Kongono\ViewInspection\Exception\ViewInspectionProjectionException;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Throwable;

/** Constructs one fixed Safe audit value without recording or compliance claims. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewSecurityAuditProjector
{
    public function project(ViewInspection $inspection, ObservabilityContext $context): SecurityAuditEvidence
    {
        try {
            $attributes = new AttributeSet([
                $this->text('inspection.schema', ViewInspection::SCHEMA),
                $this->integer('inspection.schema_version', ViewInspection::VERSION),
                $this->boolean('inspection.structurally_validated', $inspection->structurallyValidated()),
                $this->boolean('inspection.supplied_current_evidence', $inspection->suppliedCurrentEvidence()),
                $this->boolean('inspection.generated_php_executed', false),
                $this->boolean('inspection.rendering_performed', false),
                $this->boolean('inspection.production_observed', false),
                $this->text('inspection.scope', ViewInspection::SCOPE),
                $this->text('inspection.disclosure', ViewInspection::DISCLOSURE),
                $this->text('inspection.authority', ViewInspection::AUTHORITY),
                $this->text('inspection.projection', 'constructed-not-recorded'),
            ]);

            return new SecurityAuditEvidence($context, $this->definition(), SecurityAuditOutcome::Succeeded, $attributes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ViewInspectionProjectionException) {
                throw $failure;
            }
            throw ViewInspectionProjectionException::failed('security_audit', $failure);
        }
    }

    private function definition(): SecurityAuditDefinition
    {
        return new SecurityAuditDefinition(
            new SignalIdentity('kongono.view.inspection.security', 1),
            SecurityAuditRetentionIntent::Standard,
            new SecurityAuditSchema([
                $this->field('inspection.schema', AttributeValueKind::Text, [AttributeValue::text(ViewInspection::SCHEMA)]),
                $this->field('inspection.schema_version', AttributeValueKind::Integer, [AttributeValue::integer(1)]),
                $this->field('inspection.structurally_validated', AttributeValueKind::Boolean, [AttributeValue::boolean(true)]),
                $this->field('inspection.supplied_current_evidence', AttributeValueKind::Boolean, [AttributeValue::boolean(true)]),
                $this->field('inspection.generated_php_executed', AttributeValueKind::Boolean, [AttributeValue::boolean(false)]),
                $this->field('inspection.rendering_performed', AttributeValueKind::Boolean, [AttributeValue::boolean(false)]),
                $this->field('inspection.production_observed', AttributeValueKind::Boolean, [AttributeValue::boolean(false)]),
                $this->field('inspection.scope', AttributeValueKind::Text, [AttributeValue::text(ViewInspection::SCOPE)]),
                $this->field('inspection.disclosure', AttributeValueKind::Text, [AttributeValue::text(ViewInspection::DISCLOSURE)]),
                $this->field('inspection.authority', AttributeValueKind::Text, [AttributeValue::text(ViewInspection::AUTHORITY)]),
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
