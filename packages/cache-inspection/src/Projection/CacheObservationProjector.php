<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheInspection\Exception\CacheInspectionProjectionException;
use Kongono\CacheInspection\Inspection\CacheInspection;
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
final readonly class CacheObservationProjector
{
    private const array PROJECTED_FLAGS = [
        'structurally_validated',
        'supplied_exact_current_evidence',
        'detached_runtime_evidence_supplied',
        'keys_omitted',
        'values_omitted',
        'runtime_observed',
        'production_observed',
    ];

    public function project(CacheInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.cache.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet($this->attributes($inspection)),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof CacheInspectionProjectionException) {
                throw $failure;
            }
            throw CacheInspectionProjectionException::failed('observation', $failure);
        }
    }

    /** @return list<SignalAttribute> */
    private function attributes(CacheInspection $inspection): array
    {
        $attributes = [
            $this->text('inspection.schema', CacheInspection::SCHEMA),
            $this->integer('inspection.schema_version', CacheInspection::VERSION),
            $this->text('inspection.runtime_evidence_status', $inspection->runtimeEvidenceStatus()),
        ];
        foreach ($inspection->counts() as $key => $value) {
            $attributes[] = $this->integer('inspection.' . $key, $value);
        }
        foreach (self::PROJECTED_FLAGS as $key) {
            $attributes[] = $this->boolean('inspection.' . $key, $inspection->flag($key));
        }
        $attributes[] = $this->text('inspection.scope', CacheInspection::SCOPE);
        $attributes[] = $this->text('inspection.posture', CacheInspection::POSTURE);
        $attributes[] = $this->text('inspection.disclosure', CacheInspection::DISCLOSURE);
        $attributes[] = $this->text('inspection.authority', CacheInspection::AUTHORITY);
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
