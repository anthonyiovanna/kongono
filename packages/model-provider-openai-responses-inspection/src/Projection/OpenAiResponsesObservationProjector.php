<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\OpenAiResponsesInspectionProjectionException;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspection;
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
final readonly class OpenAiResponsesObservationProjector
{
    public function project(OpenAiResponsesInspection $inspection, ObservabilityContext $context): Observation
    {
        try {
            return new Observation(
                $context,
                new SignalIdentity('kongono.openai-responses.inspection', 1),
                ObservationKind::OperationalEvent,
                ObservationStatus::Ok,
                new AttributeSet([
                    $this->text('inspection.schema', OpenAiResponsesInspection::SCHEMA),
                    $this->integer('inspection.schema_version', OpenAiResponsesInspection::VERSION),
                    $this->integer('inspection.message_count', $inspection->messageCount()),
                    $this->integer('inspection.capability_count', $inspection->capabilityCount()),
                    $this->integer('inspection.usage_quantity_count', $inspection->usageQuantityCount()),
                    $this->boolean('inspection.streaming', $inspection->streaming()),
                    $this->boolean('inspection.runtime_evidence_supplied', $inspection->runtimeEvidenceSupplied()),
                    $this->boolean('inspection.result_present', $inspection->resultPresent()),
                    $this->boolean('inspection.cost_present', $inspection->costPresent()),
                    $this->boolean('inspection.cleanup_complete', $inspection->cleanupComplete()),
                    $this->text('inspection.scope', $inspection->scope()),
                    $this->text('inspection.disclosure', $inspection->disclosure()),
                    $this->text('inspection.authority', $inspection->authority()),
                    $this->text('inspection.projection', 'constructed-not-recorded'),
                ]),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof OpenAiResponsesInspectionProjectionException) {
                throw $failure;
            }
            throw OpenAiResponsesInspectionProjectionException::failed('observation', $failure);
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
