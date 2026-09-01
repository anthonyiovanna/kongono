<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Projection;

use Kongono\AgentInspection\Exception\AgentInspectionProjectionException;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Throwable;

/** Returns one inert neutral observation without recording, buffering, or export. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentInspectionObservationProjector
{
    public function project(AgentPrincipalInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            $attributes = [
                $this->text('inspection.schema', AgentPrincipalInspection::SCHEMA),
                $this->integer('inspection.schema_version', AgentPrincipalInspection::VERSION),
                $this->integer('inspection.declared_capability_count', $inspection->declaredCapabilityCount()),
                $this->integer('inspection.required_capability_count', $inspection->requiredCapabilityCount()),
                $this->integer('inspection.optional_capability_count', $inspection->optionalCapabilityCount()),
                $this->integer('inspection.permitted_capability_count', $inspection->permittedCapabilityCount()),
                $this->integer('inspection.denied_capability_count', $inspection->deniedCapabilityCount()),
                $this->integer('inspection.constraint_count', $inspection->constraintCount()),
                $this->integer('inspection.evidence_reference_count', $inspection->evidenceReferenceCount()),
                $this->boolean('inspection.runtime_evidence_supplied', $inspection->runtimeEvidenceSupplied()),
                $this->text('inspection.scope', $inspection->scope()),
                $this->text('inspection.disclosure', $inspection->disclosure()),
                $this->text('inspection.profile_currentness', $inspection->profileCurrentness()),
                $this->text('inspection.external_authority_currentness', $inspection->externalAuthorityCurrentness()),
                $this->text('inspection.authority', $inspection->authority()),
            ];
            if (null !== $inspection->runtimeOutcome()) {
                $attributes[] = $this->text('inspection.runtime_outcome', $inspection->runtimeOutcome()->value);
            }

            return new Observation(
                $context,
                new SignalIdentity('kongono.agent.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet($attributes),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof AgentInspectionProjectionException) {
                throw $failure;
            }
            throw AgentInspectionProjectionException::invalid('observation', $failure);
        }
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
