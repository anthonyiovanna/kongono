<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationWiring;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringValidator;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationInspection\Exception\ModelInvocationInspectionBindingException;
use Kongono\ModelInvocationInspection\Exception\ModelInvocationRuntimeEvidenceException;
use Throwable;

/** Revalidates exact supplied evidence and exposes only bounded aggregate claims. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationInspector
{
    public function inspect(ModelInvocationInspectionRequest $request): ModelInvocationInspection
    {
        $profile = $request->profile();
        $supplied = $request->wiring();
        try {
            $validated = (new ModelInvocationWiringValidator())->validateCurrent($profile, $supplied->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationInspectionBindingException::mismatch('profile_wiring', $failure);
        }
        if (!$this->sameWiring($validated, $supplied)) {
            throw ModelInvocationInspectionBindingException::mismatch('profile_wiring');
        }

        $source = $profile->source();
        $artifact = $profile->artifact();
        $invocation = $source->request();
        $modelProfile = $invocation->profile();
        if (!$profile->identity()->equals($supplied->applicationProfileIdentity())
            || !$source->identity()->equals($supplied->planIdentity())
            || !$source->adapterIdentity()->equals($supplied->adapterIdentity())
            || !$invocation->identity()->equals($supplied->invocationIdentity())
            || !$modelProfile->identity()->equals($supplied->modelProfileIdentity())
            || !$modelProfile->providerIdentity()->equals($supplied->providerIdentity())
            || !$modelProfile->modelIdentity()->equals($supplied->modelIdentity())) {
            throw ModelInvocationInspectionBindingException::mismatch('profile_binding');
        }
        if (!$artifact->planIdentity()->equals($source->identity())
            || !$artifact->adapterIdentity()->equals($source->adapterIdentity())
            || !$artifact->invocationIdentity()->equals($invocation->identity())
            || !hash_equals($artifact->artifactSha256(), $supplied->artifactSha256())
            || !hash_equals($artifact->sourceSha256(), $supplied->sourceSha256())
            || !hash_equals($artifact->requestSha256(), $supplied->requestSha256())) {
            throw ModelInvocationInspectionBindingException::mismatch('artifact_binding');
        }

        $runtime = $request->runtimeResult();
        $outcome = null;
        $state = null;
        $finishReason = null;
        $eventCount = 0;
        $usageEntryCount = 0;
        $attemptCount = 0;
        $elapsed = 0;
        $retained = 0;
        $outputBytes = 0;
        $resultPresent = false;
        $costPresent = false;
        $cleanupComplete = false;
        if (null !== $runtime) {
            $receipt = $runtime->receipt();
            $result = $runtime->result();
            if (InvocationState::Retired !== $runtime->state() || !$receipt->cleanupComplete()
                || InvocationOutcome::Uncertain === $receipt->outcome()
                || !$receipt->invocationIdentity()->equals($invocation->identity())
                || !$receipt->profile()->equals($modelProfile)) {
                throw ModelInvocationRuntimeEvidenceException::mismatch('runtime_binding');
            }
            $succeeded = InvocationOutcome::Succeeded === $receipt->outcome();
            if ($succeeded !== (null !== $result) || !$this->resultMatches($result, $receipt->resultIdentity(), $invocation->identity(), $modelProfile)) {
                throw ModelInvocationRuntimeEvidenceException::mismatch('result_binding');
            }
            if (null !== $result && ((null === $result->usage()) !== (null === $receipt->usage())
                || (null !== $result->usage() && null !== $receipt->usage() && !$result->usage()->equals($receipt->usage()))
                || (null === $result->cost()) !== (null === $receipt->cost())
                || (null !== $result->cost() && null !== $receipt->cost() && !$result->cost()->equals($receipt->cost())))) {
                throw ModelInvocationRuntimeEvidenceException::mismatch('receipt_result');
            }
            foreach ($runtime->events() as $event) {
                if (!$event->invocationIdentity()->equals($invocation->identity())) {
                    throw ModelInvocationRuntimeEvidenceException::mismatch('event_binding');
                }
            }
            $outcome = $receipt->outcome();
            $state = $runtime->state();
            $finishReason = $result?->finishReason();
            $eventCount = count($runtime->events());
            $usageEntryCount = null === $receipt->usage() ? 0 : count($receipt->usage()->quantities());
            $attemptCount = $receipt->attemptCount();
            $elapsed = $runtime->elapsedMilliseconds();
            $retained = $runtime->retainedEvidenceBytes();
            $outputBytes = $this->outputBytes($result);
            $resultPresent = null !== $result;
            $costPresent = null !== $receipt->cost();
            $cleanupComplete = true;
        }

        return new ModelInvocationInspection(
            $profile->identity()->value(),
            $artifact->artifactSha256(),
            $artifact->sourceSha256(),
            $artifact->requestSha256(),
            $supplied->generatedWiringSha256(),
            $supplied->implementationSha256(),
            $invocation->version()->value,
            count($invocation->messages()),
            count($modelProfile->capabilities()->capabilities()),
            count($source->budget()->usageLimits()),
            $invocation->streaming(),
            $outcome,
            $state,
            $finishReason,
            $eventCount,
            $usageEntryCount,
            $attemptCount,
            $elapsed,
            $retained,
            $outputBytes,
            $resultPresent,
            $costPresent,
            $cleanupComplete,
        );
    }

    private function sameWiring(GeneratedModelInvocationWiring $left, GeneratedModelInvocationWiring $right): bool
    {
        return $left->applicationProfileIdentity()->equals($right->applicationProfileIdentity())
            && $left->planIdentity()->equals($right->planIdentity())
            && $left->invocationIdentity()->equals($right->invocationIdentity())
            && $left->modelProfileIdentity()->equals($right->modelProfileIdentity())
            && $left->providerIdentity()->equals($right->providerIdentity())
            && $left->modelIdentity()->equals($right->modelIdentity())
            && $left->adapterIdentity()->equals($right->adapterIdentity())
            && hash_equals($left->artifactSha256(), $right->artifactSha256())
            && hash_equals($left->sourceSha256(), $right->sourceSha256())
            && hash_equals($left->requestSha256(), $right->requestSha256())
            && hash_equals($left->implementationSha256(), $right->implementationSha256())
            && $left->generatedNamespace() === $right->generatedNamespace()
            && $left->generatedClass() === $right->generatedClass()
            && hash_equals($left->canonicalBytes(), $right->canonicalBytes())
            && hash_equals($left->generatedWiringSha256(), $right->generatedWiringSha256());
    }

    private function resultMatches(
        ?ModelResult $result,
        ?ResultIdentity $receiptResultIdentity,
        InvocationIdentity $invocationIdentity,
        ModelProfileReference $modelProfile,
    ): bool {
        if (null === $result) {
            return null === $receiptResultIdentity;
        }

        return null !== $receiptResultIdentity
            && $result->identity()->equals($receiptResultIdentity)
            && $result->invocationIdentity()->equals($invocationIdentity)
            && $result->profile()->equals($modelProfile);
    }

    private function outputBytes(?ModelResult $result): int
    {
        if (null === $result) {
            return 0;
        }
        if (null !== $result->text()) {
            return strlen($result->text()->value());
        }

        return strlen($result->actionSuggestion()?->arguments()->value() ?? '');
    }
}
