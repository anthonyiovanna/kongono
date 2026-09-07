<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Fixture;

use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
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
use Kongono\ModelInvocationContract\Result\ActionArguments;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Result\ActionSuggestion;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;

final class RuntimeResultFactory
{
    /** @param list<UsageQuantity>|null $quantities */
    public static function action(
        string $arguments = '{"day":"monday"}',
        string $action = 'calendar.read',
        ?array $quantities = null,
        ModelVisibility $visibility = ModelVisibility::Private,
        string $resultIdentity = 'result.primary',
        ?InvocationIdentity $invocationIdentity = null,
        ?ModelProfileReference $profile = null,
    ): InvocationRuntimeResult {
        $profile ??= self::profile();
        $usage = new UsageEvidence($quantities ?? [new UsageQuantity(new UsageMetricName('tokens'), new UsageUnitName('count'), 7)]);
        $result = new ModelResult(
            ContractVersion::Version1,
            new ResultIdentity($resultIdentity),
            $invocationIdentity ?? new InvocationIdentity('invocation.primary'),
            $profile,
            ResultMode::ActionSuggestion,
            FinishReason::ActionSuggested,
            null,
            new ActionSuggestion(new ActionName($action), new ActionArguments($arguments)),
            $usage,
            null,
            $visibility,
        );

        return self::successful($result);
    }

    public static function text(): InvocationRuntimeResult
    {
        $profile = self::profile();
        $usage = new UsageEvidence([new UsageQuantity(new UsageMetricName('tokens'), new UsageUnitName('count'), 7)]);
        $result = new ModelResult(
            ContractVersion::Version1,
            new ResultIdentity('result.text'),
            new InvocationIdentity('invocation.primary'),
            $profile,
            ResultMode::Text,
            FinishReason::Completed,
            new ResultText('bounded text'),
            null,
            $usage,
            null,
            ModelVisibility::Private,
        );

        return self::successful($result);
    }

    public static function unsuccessful(InvocationOutcome $outcome): InvocationRuntimeResult
    {
        $attempts = InvocationOutcome::Rejected === $outcome ? 0 : 1;
        $receipt = new InvocationReceipt(
            new ReceiptIdentity('receipt.primary'),
            new InvocationIdentity('invocation.primary'),
            self::profile(),
            $outcome,
            $attempts,
            null,
            null,
            null,
            true,
        );

        return new InvocationRuntimeResult($receipt, null, [], InvocationState::Retired, 1, 0);
    }

    public static function profile(string $identity = 'profile.primary'): ModelProfileReference
    {
        return new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity($identity),
            new ProviderIdentity('provider.primary'),
            new ModelIdentity('model.primary'),
            new ProviderCapabilitySet([
                ProviderCapability::TextResult,
                ProviderCapability::ActionSuggestion,
                ProviderCapability::UsageEvidence,
            ]),
        );
    }

    private static function successful(ModelResult $result): InvocationRuntimeResult
    {
        $receipt = new InvocationReceipt(
            new ReceiptIdentity('receipt.primary'),
            $result->invocationIdentity(),
            $result->profile(),
            InvocationOutcome::Succeeded,
            1,
            $result->identity(),
            $result->usage(),
            $result->cost(),
            true,
        );

        return new InvocationRuntimeResult($receipt, $result, [], InvocationState::Retired, 1, 0);
    }

    private function __construct() {}
}
