<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\AssetInspectionProjectionException;
use Kongono\AssetInspection\Inspection\AssetInspection;
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

/** Constructs one neutral in-memory observation without recording or export. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetObservationProjector
{
    public function project(AssetInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.asset.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet($this->attributes($inspection)),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof AssetInspectionProjectionException) {
                throw $failure;
            }
            throw AssetInspectionProjectionException::failed('observation', $failure);
        }
    }

    /** @return list<SignalAttribute> */
    private function attributes(AssetInspection $inspection): array
    {
        $attributes = [
            $this->text('inspection.schema', AssetInspection::SCHEMA),
            $this->integer('inspection.schema_version', AssetInspection::VERSION),
            $this->text('inspection.producer_kind', $inspection->producerKind()),
        ];
        foreach ($inspection->counts() as $key => $value) {
            $attributes[] = $this->integer('inspection.' . $key, $value);
        }
        foreach ($inspection->flags() as $key => $value) {
            $attributes[] = $this->boolean('inspection.' . $key, $value);
        }
        $attributes[] = $this->text('inspection.scope', AssetInspection::SCOPE);
        $attributes[] = $this->text('inspection.posture', AssetInspection::POSTURE);
        $attributes[] = $this->text('inspection.disclosure', AssetInspection::DISCLOSURE);
        $attributes[] = $this->text('inspection.authority', AssetInspection::AUTHORITY);
        $attributes[] = $this->text('inspection.projection', 'constructed-not-recorded');

        return $attributes;
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
