<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Fixture;

use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Budget\CostLimit;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Cost\CostEvidence;
use Kongono\ModelInvocationContract\Cost\CostEvidenceKind;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationLimits;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationContract\Result\ActionArguments;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelInvocationContract\Stream\ModelStreamEvent;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationContract\Stream\StreamSequence;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;

final class RuntimeFixture
{
    public static function source(
        bool $streaming = false,
        int $maxOutputBytes = 32,
        int $maxStreamEvents = 8,
        int $maxUsageEntries = 1,
        int $maxDurationMilliseconds = 100,
        int $maxRetainedEvidenceBytes = 64,
        string $adapterIdentity = 'adapter.test',
        ResultMode $resultMode = ResultMode::Text,
        bool $includeUsageLimit = true,
        bool $includeCostLimit = true,
        int $maxUsageQuantity = 10,
        string $maxCostAmount = '1.25',
    ): InvocationPlanSource {
        $capabilities = new ProviderCapabilitySet([
            ProviderCapability::TextResult,
            ProviderCapability::StructuredResult,
            ProviderCapability::ActionSuggestion,
            ProviderCapability::Streaming,
            ProviderCapability::UsageEvidence,
            ProviderCapability::CostEvidence,
        ]);
        $profile = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity('profile.test'),
            new ProviderIdentity('provider.test'),
            new ModelIdentity('model.test'),
            $capabilities,
        );

        return new InvocationPlanSource(
            new InvocationPlanIdentity('plan.test'),
            new AdapterIdentity($adapterIdentity),
            new InvocationRequest(
                ContractVersion::Version1,
                new InvocationIdentity('invocation.test'),
                $profile,
                [new InputMessage(InputRole::User, ModelVisibility::Private, 'secret input')],
                $resultMode,
                $streaming,
                new InvocationLimits($maxOutputBytes, $maxStreamEvents, $maxUsageEntries),
            ),
            new InvocationExecutionBudget(
                $maxDurationMilliseconds,
                1,
                $maxRetainedEvidenceBytes,
                $includeUsageLimit ? [new UsageLimit(new UsageMetricName('tokens'), new UsageUnitName('count'), $maxUsageQuantity)] : [],
                $includeCostLimit ? new CostLimit(new CurrencyCode('USD'), new DecimalAmount($maxCostAmount)) : null,
            ),
        );
    }

    public static function result(
        InvocationPlanSource $source,
        string $text = 'ok',
        ?UsageEvidence $usage = null,
        ?CostEvidence $cost = null,
        ?InvocationIdentity $invocationIdentity = null,
        ?ModelProfileReference $profile = null,
        ?ResultMode $mode = null,
        ?FinishReason $finishReason = null,
    ): ModelResult {
        $mode ??= $source->request()->resultMode();
        $actionMode = ResultMode::ActionSuggestion === $mode;

        return new ModelResult(
            ContractVersion::Version1,
            new ResultIdentity('result.test'),
            $invocationIdentity ?? $source->request()->identity(),
            $profile ?? $source->request()->profile(),
            $mode,
            $finishReason ?? ($actionMode ? FinishReason::ActionSuggested : FinishReason::Completed),
            $actionMode ? null : new ResultText($text),
            $actionMode ? new ActionSuggestion(new ActionName('action.test'), new ActionArguments($text)) : null,
            $usage,
            $cost,
            ModelVisibility::Private,
        );
    }

    public static function usage(int $quantity): UsageEvidence
    {
        return new UsageEvidence([new UsageQuantity(new UsageMetricName('tokens'), new UsageUnitName('count'), $quantity)]);
    }

    public static function cost(string $amount, string $currency = 'USD'): CostEvidence
    {
        return new CostEvidence(CostEvidenceKind::Estimated, new CurrencyCode($currency), new DecimalAmount($amount));
    }

    public static function event(
        InvocationPlanSource $source,
        int $sequence,
        StreamEventKind $kind,
        ?string $payload = null,
        ?UsageEvidence $usage = null,
        ?InvocationIdentity $invocationIdentity = null,
    ): InvocationAdapterEmission {
        return InvocationAdapterEmission::event(new ModelStreamEvent($invocationIdentity ?? $source->request()->identity(), new StreamSequence($sequence), $kind, $payload, $usage));
    }

    public static function request(
        InvocationPlanSource $source,
        FakeInvocationAdapter $adapter,
        ?FakeInvocationCancellation $cancellation = null,
        ?FakeInvocationMonotonicClock $clock = null,
        ?string $artifactBytes = null,
    ): InvocationRuntimeRequest {
        return new InvocationRuntimeRequest(
            $source,
            $artifactBytes ?? (new InvocationPlanArtifactBuilder())->build($source)->canonicalBytes(),
            new ReceiptIdentity('receipt.test'),
            $adapter,
            $cancellation ?? new FakeInvocationCancellation(),
            $clock ?? new FakeInvocationMonotonicClock(),
        );
    }

    private function __construct() {}
}
