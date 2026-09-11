<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Inspection;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfileFactory;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\OpenAiResponsesInspectionBindingException;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\OpenAiResponsesRuntimeEvidenceException;
use Throwable;

/** Revalidates supplied evidence without reading hidden model or transport content. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesInspector
{
    public function inspect(OpenAiResponsesInspectionRequest $request): OpenAiResponsesInspection
    {
        $supplied = $request->profile();
        try {
            $current = (new OpenAiResponsesApplicationProfileFactory())->create(
                $supplied->providerProfile(),
                $supplied->modelInvocationProfile(),
                $supplied->externalServiceProfile(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw OpenAiResponsesInspectionBindingException::mismatch('profile_currentness', $failure);
        }
        if (!$current->identity()->equals($supplied->identity())
            || !$current->providerProfile()->equals($supplied->providerProfile())
            || !$current->modelInvocationProfile()->identity()->equals($supplied->modelInvocationProfile()->identity())
            || !$current->externalServiceProfile()->identity()->equals($supplied->externalServiceProfile()->identity())) {
            throw OpenAiResponsesInspectionBindingException::mismatch('profile_binding');
        }

        $provider = $supplied->providerProfile();
        $modelApplication = $supplied->modelInvocationProfile();
        $source = $modelApplication->source();
        $artifact = $modelApplication->artifact();
        $invocation = $source->request();
        $neutral = $invocation->profile();
        $external = $supplied->externalServiceProfile();
        if ($source->adapterIdentity()->value() !== OpenAiResponsesInvocationAdapter::IDENTITY
            || !$provider->neutralProfile()->equals($neutral)
            || !$artifact->planIdentity()->equals($source->identity())
            || !$artifact->adapterIdentity()->equals($source->adapterIdentity())
            || !$artifact->invocationIdentity()->equals($invocation->identity())
            || !array_any($external->bindingArtifact()->bindings(), static fn($binding): bool => $binding->identity()->equals($external->binding()->identity()))) {
            throw OpenAiResponsesInspectionBindingException::mismatch('component_binding');
        }
        $structured = null !== $provider->structuredOutput();
        $function = null !== $provider->functionDefinition();
        if ($structured && $function) {
            throw OpenAiResponsesInspectionBindingException::mismatch('mapping_surface');
        }

        $runtime = $request->runtimeResult();
        $receiptDigest = null;
        $resultDigest = null;
        $outcome = 'not-supplied';
        $lifecycle = 'not-supplied';
        $finishReason = 'none';
        $usageCount = 0;
        $usageTotal = 0;
        $eventCount = 0;
        $attemptCount = 0;
        $elapsed = 0;
        $retained = 0;
        $resultPresent = false;
        $usagePresent = false;
        $costPresent = false;
        $eventsPresent = false;
        $cleanupComplete = false;
        if (null !== $runtime) {
            $receipt = $runtime->receipt();
            $result = $runtime->result();
            if (InvocationState::Retired !== $runtime->state() || !$receipt->cleanupComplete()
                || !$receipt->invocationIdentity()->equals($invocation->identity())
                || !$receipt->profile()->equals($neutral)) {
                throw OpenAiResponsesRuntimeEvidenceException::mismatch('runtime_binding');
            }
            $succeeded = InvocationOutcome::Succeeded === $receipt->outcome();
            if ($succeeded !== (null !== $result) || $succeeded !== (null !== $receipt->resultIdentity())) {
                throw OpenAiResponsesRuntimeEvidenceException::mismatch('result_binding');
            }
            if (null !== $result && (null === $receipt->resultIdentity()
                || !$result->identity()->equals($receipt->resultIdentity())
                || !$result->invocationIdentity()->equals($invocation->identity())
                || !$result->profile()->equals($neutral)
                || $result->mode() !== $invocation->resultMode()
                || (null === $result->usage()) !== (null === $receipt->usage())
                || (null !== $result->usage() && null !== $receipt->usage() && !$result->usage()->equals($receipt->usage()))
                || (null === $result->cost()) !== (null === $receipt->cost())
                || (null !== $result->cost() && null !== $receipt->cost() && !$result->cost()->equals($receipt->cost())))) {
                throw OpenAiResponsesRuntimeEvidenceException::mismatch('receipt_result');
            }
            foreach ($runtime->events() as $position => $event) {
                if (!$event->invocationIdentity()->equals($invocation->identity()) || $event->sequence()->value() !== $position + 1) {
                    throw OpenAiResponsesRuntimeEvidenceException::mismatch('event_binding');
                }
            }
            $usage = $receipt->usage();
            if (null !== $usage) {
                $usageCount = count($usage->quantities());
                foreach ($usage->quantities() as $quantity) {
                    $usageTotal += $quantity->quantity();
                }
            }
            $receiptDigest = hash('sha256', $receipt->identity()->value());
            $resultDigest = null === $result ? null : hash('sha256', $result->identity()->value());
            $outcome = $receipt->outcome()->value;
            $lifecycle = $runtime->state()->value;
            $finishReason = null === $result ? 'none' : $result->finishReason()->value;
            $eventCount = count($runtime->events());
            $attemptCount = $receipt->attemptCount();
            $elapsed = $runtime->elapsedMilliseconds();
            $retained = $runtime->retainedEvidenceBytes();
            $resultPresent = null !== $result;
            $usagePresent = null !== $receipt->usage();
            $costPresent = null !== $receipt->cost();
            $eventsPresent = 0 !== $eventCount;
            $cleanupComplete = true;
        }

        return new OpenAiResponsesInspection(
            [
                'application_profile_sha256' => $supplied->identity()->value(),
                'neutral_application_profile_sha256' => $modelApplication->identity()->value(),
                'plan_source_sha256' => $artifact->sourceSha256(),
                'plan_artifact_sha256' => $artifact->artifactSha256(),
                'plan_request_sha256' => $artifact->requestSha256(),
                'external_service_profile_sha256' => $external->identity()->value(),
                'binding_sha256' => hash('sha256', $external->binding()->identity()->value()),
                'receipt_sha256' => $receiptDigest,
                'result_sha256' => $resultDigest,
            ],
            [
                'mapping_version' => $provider->mappingVersion()->value,
                'result_mode' => $invocation->resultMode()->value,
                'storage_preference' => $provider->storagePreference()->value,
                'streaming' => $invocation->streaming() ? 'enabled' : 'disabled',
                'outcome' => $outcome,
                'lifecycle' => $lifecycle,
                'finish_reason' => $finishReason,
                'evidence_posture' => OpenAiResponsesInspection::EVIDENCE_POSTURE,
            ],
            [
                'structured_output_present' => $structured,
                'function_definition_present' => $function,
                'runtime_evidence_present' => null !== $runtime,
                'result_present' => $resultPresent,
                'usage_present' => $usagePresent,
                'cost_present' => $costPresent,
                'events_present' => $eventsPresent,
                'cleanup_complete' => $cleanupComplete,
            ],
            [
                'message_count' => count($invocation->messages()),
                'capability_count' => count($neutral->capabilities()->capabilities()),
                'usage_quantity_count' => $usageCount,
                'usage_quantity_total' => $usageTotal,
                'event_count' => $eventCount,
                'attempt_count' => $attemptCount,
                'elapsed_milliseconds' => $elapsed,
                'retained_evidence_bytes' => $retained,
            ],
        );
    }
}
