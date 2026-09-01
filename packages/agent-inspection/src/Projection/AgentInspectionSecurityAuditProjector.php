<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Projection;

use Kongono\AgentInspection\Exception\AgentInspectionProjectionException;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
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
use Throwable;

/** Returns one fixed Safe audit evidence projection without recording it. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentInspectionSecurityAuditProjector
{
    public function project(AgentPrincipalInspection $inspection, ObservabilityContext $context): SecurityAuditEvidence
    {
        try {
            $attributes = new AttributeSet([
                $this->text('inspection.schema', AgentPrincipalInspection::SCHEMA),
                $this->integer('inspection.schema_version', AgentPrincipalInspection::VERSION),
                $this->boolean('inspection.runtime_evidence_supplied', $inspection->runtimeEvidenceSupplied()),
                $this->text('inspection.scope', $inspection->scope()),
                $this->text('inspection.disclosure', $inspection->disclosure()),
                $this->text('inspection.profile_currentness', $inspection->profileCurrentness()),
                $this->text('inspection.external_authority_currentness', $inspection->externalAuthorityCurrentness()),
                $this->text('inspection.authority', $inspection->authority()),
                $this->text('inspection.projection', 'succeeded'),
            ]);

            return new SecurityAuditEvidence($context, $this->definition(), SecurityAuditOutcome::Succeeded, $attributes);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof AgentInspectionProjectionException) {
                throw $failure;
            }
            throw AgentInspectionProjectionException::invalid('security_audit', $failure);
        }
    }

    private function definition(): SecurityAuditDefinition
    {
        return new SecurityAuditDefinition(
            new SignalIdentity('kongono.agent.inspection.security', 1),
            SecurityAuditRetentionIntent::Standard,
            new SecurityAuditSchema([
                $this->field('inspection.schema', AttributeValueKind::Text, [AttributeValue::text(AgentPrincipalInspection::SCHEMA)]),
                $this->field('inspection.schema_version', AttributeValueKind::Integer, [AttributeValue::integer(1)]),
                $this->field('inspection.runtime_evidence_supplied', AttributeValueKind::Boolean, [AttributeValue::boolean(false), AttributeValue::boolean(true)]),
                $this->field('inspection.scope', AttributeValueKind::Text, [AttributeValue::text(AgentPrincipalInspection::SCOPE)]),
                $this->field('inspection.disclosure', AttributeValueKind::Text, [AttributeValue::text(AgentPrincipalInspection::DISCLOSURE)]),
                $this->field('inspection.profile_currentness', AttributeValueKind::Text, [AttributeValue::text(AgentPrincipalInspection::PROFILE_CURRENTNESS)]),
                $this->field('inspection.external_authority_currentness', AttributeValueKind::Text, [AttributeValue::text(AgentPrincipalInspection::EXTERNAL_AUTHORITY_CURRENTNESS)]),
                $this->field('inspection.authority', AttributeValueKind::Text, [AttributeValue::text(AgentPrincipalInspection::AUTHORITY)]),
                $this->field('inspection.projection', AttributeValueKind::Text, [AttributeValue::text('succeeded')]),
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
