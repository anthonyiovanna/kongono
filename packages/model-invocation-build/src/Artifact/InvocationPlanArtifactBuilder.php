<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Artifact;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanArtifactException;
use Kongono\ModelInvocationBuild\Internal\CanonicalJson;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;

/** Builds privacy-preserving canonical plan evidence from one explicit typed source. */
#[Api(ApiStatus::Experimental)]
final class InvocationPlanArtifactBuilder
{
    private const int MAX_ARTIFACT_BYTES = 65_536;

    public function build(InvocationPlanSource $source): InvocationPlanArtifact
    {
        try {
            $request = self::requestData($source->request());
            $requestSha256 = hash('sha256', CanonicalJson::encode($request));
            $budget = self::budgetData($source->budget());
            $sourceSha256 = hash('sha256', CanonicalJson::encode([
                'plan_id' => $source->identity()->value(),
                'adapter_id' => $source->adapterIdentity()->value(),
                'request' => $request,
                'budget' => $budget,
            ]));
            $profile = $source->request()->profile();
            $limits = $source->request()->limits();
            $artifactBytes = CanonicalJson::encode([
                'schema' => 'kongono.model-invocation-plan-artifact',
                'artifact_version' => 1,
                'contract_version' => 1,
                'builder' => ['package' => 'kongono/model-invocation-build', 'version' => 1],
                'plan' => [
                    'plan_id' => $source->identity()->value(),
                    'adapter_id' => $source->adapterIdentity()->value(),
                    'invocation_id' => $source->request()->identity()->value(),
                    'profile' => [
                        'profile_id' => $profile->identity()->value(),
                        'provider_id' => $profile->providerIdentity()->value(),
                        'model_id' => $profile->modelIdentity()->value(),
                        'capabilities' => array_map(static fn(ProviderCapability $capability): string => $capability->value, $profile->capabilities()->capabilities()),
                    ],
                    'request' => [
                        'request_sha256' => $requestSha256,
                        'result_mode' => $source->request()->resultMode()->value,
                        'streaming' => $source->request()->streaming(),
                        'limits' => [
                            'max_output_bytes' => $limits->maxOutputBytes(),
                            'max_stream_events' => $limits->maxStreamEvents(),
                            'max_usage_entries' => $limits->maxUsageEntries(),
                        ],
                    ],
                    'budget' => $budget,
                ],
                'source_sha256' => $sourceSha256,
            ]);
        } catch (JsonException $failure) {
            throw InvalidInvocationPlanArtifactException::invalid('encoding_failed', previous: $failure);
        }
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidInvocationPlanArtifactException::invalid('artifact_bytes', artifactBytes: strlen($artifactBytes), limit: self::MAX_ARTIFACT_BYTES);
        }

        return new InvocationPlanArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSha256,
            $requestSha256,
            $source->identity(),
            $source->adapterIdentity(),
            $source->request()->identity(),
        );
    }

    /** @return array<string, mixed> */
    private static function requestData(InvocationRequest $request): array
    {
        $profile = $request->profile();
        $limits = $request->limits();

        return [
            'contract_version' => $request->version()->value,
            'invocation_id' => $request->identity()->value(),
            'profile' => [
                'profile_id' => $profile->identity()->value(),
                'provider_id' => $profile->providerIdentity()->value(),
                'model_id' => $profile->modelIdentity()->value(),
                'capabilities' => array_map(static fn(ProviderCapability $capability): string => $capability->value, $profile->capabilities()->capabilities()),
            ],
            'messages' => array_map(static fn(InputMessage $message): array => [
                'role' => $message->role()->value,
                'visibility' => $message->visibility()->value,
                'content' => $message->content(),
            ], $request->messages()),
            'result_mode' => $request->resultMode()->value,
            'streaming' => $request->streaming(),
            'limits' => [
                'max_output_bytes' => $limits->maxOutputBytes(),
                'max_stream_events' => $limits->maxStreamEvents(),
                'max_usage_entries' => $limits->maxUsageEntries(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function budgetData(InvocationExecutionBudget $budget): array
    {
        $cost = $budget->costLimit();

        return [
            'max_duration_milliseconds' => $budget->maxDurationMilliseconds(),
            'max_attempts' => $budget->maxAttempts(),
            'max_retained_evidence_bytes' => $budget->maxRetainedEvidenceBytes(),
            'usage_limits' => array_map(static fn(UsageLimit $limit): array => [
                'metric' => $limit->metric()->value(),
                'unit' => $limit->unit()->value(),
                'maximum_quantity' => $limit->maximumQuantity(),
            ], $budget->usageLimits()),
            'cost_limit' => null === $cost ? null : [
                'currency' => $cost->currency()->value(),
                'maximum_amount' => $cost->maximumAmount()->value(),
            ],
        ];
    }
}
