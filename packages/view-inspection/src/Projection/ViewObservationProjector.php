<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Projection;

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
use Kongono\ViewInspection\Exception\ViewInspectionProjectionException;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Throwable;

/** Constructs one neutral operational event without recording or export. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewObservationProjector
{
    public function project(ViewInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.view.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet([
                    $this->text('inspection.schema', ViewInspection::SCHEMA),
                    $this->integer('inspection.schema_version', ViewInspection::VERSION),
                    $this->integer('inspection.view_count', $inspection->viewCount()),
                    $this->integer('inspection.artifact_file_count', $inspection->artifactFileCount()),
                    $this->boolean('inspection.structurally_validated', $inspection->structurallyValidated()),
                    $this->boolean('inspection.supplied_current_evidence', $inspection->suppliedCurrentEvidence()),
                    $this->boolean('inspection.generated_php_executed', false),
                    $this->boolean('inspection.rendering_performed', false),
                    $this->boolean('inspection.production_observed', false),
                    $this->text('inspection.scope', ViewInspection::SCOPE),
                    $this->text('inspection.disclosure', ViewInspection::DISCLOSURE),
                    $this->text('inspection.authority', ViewInspection::AUTHORITY),
                    $this->text('inspection.projection', 'constructed-not-recorded'),
                ]),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof ViewInspectionProjectionException) {
                throw $failure;
            }
            throw ViewInspectionProjectionException::failed('observation', $failure);
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
