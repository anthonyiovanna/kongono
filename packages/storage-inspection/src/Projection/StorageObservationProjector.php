<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Projection;

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
use Kongono\StorageInspection\Exception\StorageInspectionProjectionException;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Throwable;

/** Constructs one neutral in-memory observation without recording or export. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageObservationProjector
{
    private const array PROJECTED_FLAGS = [
        'structurally_validated',
        'supplied_exact_current_evidence',
        'detached_runtime_evidence_supplied',
        'object_identities_omitted',
        'object_contents_omitted',
        'provider_access',
        'adapter_access',
        'runtime_observed',
        'production_observed',
    ];

    public function project(StorageInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.storage.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet($this->attributes($inspection)),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof StorageInspectionProjectionException) {
                throw $failure;
            }
            throw StorageInspectionProjectionException::failed('observation', $failure);
        }
    }

    /** @return list<SignalAttribute> */
    private function attributes(StorageInspection $inspection): array
    {
        $attributes = [
            $this->text('inspection.schema', StorageInspection::SCHEMA),
            $this->integer('inspection.schema_version', StorageInspection::VERSION),
            $this->text('inspection.runtime_evidence_status', $inspection->runtimeEvidenceStatus()),
        ];
        $counts = $inspection->counts();
        foreach (['profile_count', 'outcome_total', 'mutation_uncertain'] as $key) {
            $attributes[] = $this->integer('inspection.' . $key, $counts[$key]);
        }
        foreach (self::PROJECTED_FLAGS as $key) {
            $attributes[] = $this->boolean('inspection.' . $key, $inspection->flag($key));
        }
        $attributes[] = $this->text('inspection.scope', StorageInspection::SCOPE);
        $attributes[] = $this->text('inspection.posture', StorageInspection::POSTURE);
        $attributes[] = $this->text('inspection.disclosure', StorageInspection::DISCLOSURE);
        $attributes[] = $this->text('inspection.authority', StorageInspection::AUTHORITY);
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
