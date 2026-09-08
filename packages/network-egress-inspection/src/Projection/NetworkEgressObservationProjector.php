<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressInspection\Exception\NetworkEgressInspectionProjectionException;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspection;
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

/** Constructs one neutral operational event without recording or export. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressObservationProjector
{
    public function project(NetworkEgressInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.network-egress.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet([
                    $this->text('inspection.schema', NetworkEgressInspection::SCHEMA),
                    $this->integer('inspection.schema_version', NetworkEgressInspection::VERSION),
                    $this->integer('inspection.allowed_origin_count', $inspection->allowedOriginCount()),
                    $this->integer('inspection.required_capability_count', $inspection->requiredCapabilityCount()),
                    $this->integer('inspection.maximum_connection_attempts', $inspection->maximumConnectionAttempts()),
                    $this->boolean('inspection.runtime_evidence_supplied', $inspection->runtimeEvidenceSupplied()),
                    $this->boolean('inspection.response_present', $inspection->responsePresent()),
                    $this->boolean('inspection.failure_present', $inspection->failurePresent()),
                    $this->boolean('inspection.cleanup_complete', $inspection->cleanupComplete()),
                    $this->text('inspection.scope', $inspection->scope()),
                    $this->text('inspection.disclosure', $inspection->disclosure()),
                    $this->text('inspection.authority', $inspection->authority()),
                    $this->text('inspection.projection', 'constructed-not-recorded'),
                ]),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof NetworkEgressInspectionProjectionException) {
                throw $failure;
            }
            throw NetworkEgressInspectionProjectionException::failed('observation', $failure);
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
