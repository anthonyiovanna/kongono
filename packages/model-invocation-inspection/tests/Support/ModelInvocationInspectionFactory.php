<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationWiring;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
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
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\InvocationReceipt;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
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
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;

final class ModelInvocationInspectionFactory
{
    public const string SECRET = 'raw-secret-message-provider.example/api?token=credential';
    public const string RESULT_SECRET = 'raw-private-result-payload';

    public static function source(string $suffix = 'inspection', bool $streaming = true): InvocationPlanSource
    {
        $profile = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity('profile.' . $suffix),
            new ProviderIdentity('provider.' . $suffix),
            new ModelIdentity('model.' . $suffix),
            new ProviderCapabilitySet([
                ProviderCapability::TextResult,
                ProviderCapability::Streaming,
                ProviderCapability::UsageEvidence,
                ProviderCapability::CostEvidence,
            ]),
        );

        return new InvocationPlanSource(
            new InvocationPlanIdentity('plan.' . $suffix),
            new AdapterIdentity('adapter.' . $suffix),
            new InvocationRequest(
                ContractVersion::Version1,
                new InvocationIdentity('invocation.' . $suffix),
                $profile,
                [new InputMessage(InputRole::User, ModelVisibility::Private, self::SECRET . '.' . $suffix)],
                ResultMode::Text,
                $streaming,
                new InvocationLimits(1_024, 8, 1),
            ),
            new InvocationExecutionBudget(
                1_000,
                1,
                2_048,
                [new UsageLimit(new UsageMetricName('tokens'), new UsageUnitName('count'), 100)],
                new CostLimit(new CurrencyCode('USD'), new DecimalAmount('2.00')),
            ),
        );
    }

    public static function profile(?InvocationPlanSource $source = null): ModelInvocationApplicationProfile
    {
        $source ??= self::source();
        $artifact = (new InvocationPlanArtifactBuilder())->build($source);

        return (new ModelInvocationApplicationProfileFactory())->create($source, $artifact->canonicalBytes());
    }

    public static function wiring(?ModelInvocationApplicationProfile $profile = null): GeneratedModelInvocationWiring
    {
        return (new ModelInvocationWiringGenerator())->generate($profile ?? self::profile());
    }

    public static function runtime(?InvocationPlanSource $source = null, InvocationOutcome $outcome = InvocationOutcome::Succeeded): InvocationRuntimeResult
    {
        $source ??= self::source();
        $usage = null;
        $cost = null;
        $result = null;
        if (InvocationOutcome::Succeeded === $outcome) {
            $usage = self::usage();
            $cost = self::cost();
            $result = self::result($source, $usage, $cost);
        }
        $receipt = new InvocationReceipt(
            new ReceiptIdentity('receipt.inspection'),
            $source->request()->identity(),
            $source->request()->profile(),
            $outcome,
            InvocationOutcome::Rejected === $outcome ? 0 : 1,
            $result?->identity(),
            $usage,
            $cost,
            true,
        );
        $events = [];
        if (InvocationOutcome::Succeeded === $outcome) {
            $events = [
                new ModelStreamEvent($source->request()->identity(), new StreamSequence(1), StreamEventKind::Started),
                new ModelStreamEvent($source->request()->identity(), new StreamSequence(2), StreamEventKind::ContentDelta, self::RESULT_SECRET),
                new ModelStreamEvent($source->request()->identity(), new StreamSequence(3), StreamEventKind::Usage, usage: $usage),
                new ModelStreamEvent($source->request()->identity(), new StreamSequence(4), StreamEventKind::Completed),
            ];
        }

        return new InvocationRuntimeResult($receipt, $result, $events, InvocationState::Retired, 12, 48);
    }

    public static function mismatchedRuntime(InvocationPlanSource $source): InvocationRuntimeResult
    {
        $other = self::source('other');
        $receipt = new InvocationReceipt(
            new ReceiptIdentity('receipt.other'),
            $other->request()->identity(),
            $other->request()->profile(),
            InvocationOutcome::Failed,
            1,
            null,
            null,
            null,
            true,
        );

        return new InvocationRuntimeResult($receipt, null, [], InvocationState::Retired, 1, 1);
    }

    public static function graphView(GraphRole $role = GraphRole::Chie): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('a', 64),
            new GraphIdentity(str_repeat('b', 64)),
            GraphContractVersion::Version1,
            10,
            6,
            ['composition.input' => 1, 'composition.service' => 4, 'composition.contract' => 3, 'composition.root' => 2],
            ['composition.depends_on' => 2, 'composition.consumes_input' => 1, 'composition.implements_contract' => 2, 'composition.exposes_root' => 1],
        );
    }

    private static function result(InvocationPlanSource $source, UsageEvidence $usage, CostEvidence $cost): ModelResult
    {
        return new ModelResult(
            ContractVersion::Version1,
            new ResultIdentity('result.inspection'),
            $source->request()->identity(),
            $source->request()->profile(),
            ResultMode::Text,
            FinishReason::Completed,
            new ResultText(self::RESULT_SECRET),
            null,
            $usage,
            $cost,
            ModelVisibility::Private,
        );
    }

    private static function usage(): UsageEvidence
    {
        return new UsageEvidence([new UsageQuantity(new UsageMetricName('tokens'), new UsageUnitName('count'), 7)]);
    }

    private static function cost(): CostEvidence
    {
        return new CostEvidence(CostEvidenceKind::Estimated, new CurrencyCode('USD'), new DecimalAmount('1.25'));
    }

    private function __construct() {}
}
