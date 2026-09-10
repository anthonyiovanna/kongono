<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceInspectionProjectionException;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
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
final readonly class ExternalServiceObservationProjector
{
    public function project(ExternalServiceInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.external-service.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet([
                    $this->text('inspection.schema', ExternalServiceInspection::SCHEMA),
                    $this->integer('inspection.schema_version', ExternalServiceInspection::VERSION),
                    $this->integer('inspection.binding_count', $inspection->bindingCount()),
                    $this->integer('inspection.allowed_origin_count', $inspection->allowedOriginCount()),
                    $this->integer('inspection.required_capability_count', $inspection->requiredCapabilityCount()),
                    $this->boolean('inspection.completed_result_supplied', $inspection->resultSupplied()),
                    $this->boolean('inspection.response_present', $inspection->responsePresent()),
                    $this->boolean('inspection.failure_present', $inspection->failurePresent()),
                    $this->boolean('inspection.credential_cleanup_complete', $inspection->credentialCleanupComplete()),
                    $this->boolean('inspection.possible_effect_uncertainty', $inspection->possibleEffectUncertainty()),
                    $this->text('inspection.scope', $inspection->scope()),
                    $this->text('inspection.disclosure', $inspection->disclosure()),
                    $this->text('inspection.authority', $inspection->authority()),
                    $this->text('inspection.projection', 'constructed-not-recorded'),
                ]),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ExternalServiceInspectionProjectionException) {
                throw $failure;
            }
            throw ExternalServiceInspectionProjectionException::failed('observation', $failure);
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
