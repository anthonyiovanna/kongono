<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Support;

use Kongono\ModelInvocationBuild\Budget\CostLimit;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\ContractVersion;
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
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;

final class InvocationPlanSourceFactory
{
    public static function create(string $message = 'private prompt', string $planId = 'plan.primary'): InvocationPlanSource
    {
        $profile = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity('profile.primary'),
            new ProviderIdentity('provider.primary'),
            new ModelIdentity('model.primary'),
            new ProviderCapabilitySet([
                ProviderCapability::TextResult,
                ProviderCapability::Streaming,
                ProviderCapability::UsageEvidence,
                ProviderCapability::CostEvidence,
            ]),
        );
        $request = new InvocationRequest(
            ContractVersion::Version1,
            new InvocationIdentity('invocation.primary'),
            $profile,
            [
                new InputMessage(InputRole::System, ModelVisibility::Protected, 'bounded system context'),
                new InputMessage(InputRole::User, ModelVisibility::Private, $message),
            ],
            ResultMode::Text,
            true,
            new InvocationLimits(4096, 64, 2),
        );
        $budget = new InvocationExecutionBudget(
            30_000,
            1,
            65_536,
            [
                new UsageLimit(new UsageMetricName('input_tokens'), new UsageUnitName('tokens'), 4_096),
                new UsageLimit(new UsageMetricName('output_tokens'), new UsageUnitName('tokens'), 2_048),
            ],
            new CostLimit(new CurrencyCode('USD'), new DecimalAmount('1.25')),
        );

        return new InvocationPlanSource(
            new InvocationPlanIdentity($planId),
            new AdapterIdentity('adapter.primary'),
            $request,
            $budget,
        );
    }

    private function __construct() {}
}
