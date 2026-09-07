<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Support;

use Kongono\ModelInvocationApplication\Generated\GeneratedModelInvocationWiring;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplicationRunInput;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\ContractVersion;
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
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;

final class ModelInvocationApplicationFactory
{
    public static function source(string $secret = 'private-message', string $adapter = 'adapter.application'): InvocationPlanSource
    {
        $profile = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity('profile.application'),
            new ProviderIdentity('provider.application'),
            new ModelIdentity('model.application'),
            new ProviderCapabilitySet([ProviderCapability::TextResult]),
        );

        return new InvocationPlanSource(
            new InvocationPlanIdentity('plan.application'),
            new AdapterIdentity($adapter),
            new InvocationRequest(
                ContractVersion::Version1,
                new InvocationIdentity('invocation.application'),
                $profile,
                [new InputMessage(InputRole::User, ModelVisibility::Private, $secret)],
                ResultMode::Text,
                false,
                new InvocationLimits(1_024, 8, 0),
            ),
            new InvocationExecutionBudget(1_000, 1, 2_048, [], null),
        );
    }

    public static function artifactBytes(?InvocationPlanSource $source = null): string
    {
        return (new InvocationPlanArtifactBuilder())->build($source ?? self::source())->canonicalBytes();
    }

    public static function profile(?InvocationPlanSource $source = null): ModelInvocationApplicationProfile
    {
        $source ??= self::source();

        return (new ModelInvocationApplicationProfileFactory())->create($source, self::artifactBytes($source));
    }

    public static function wiring(?ModelInvocationApplicationProfile $profile = null): GeneratedModelInvocationWiring
    {
        return (new ModelInvocationWiringGenerator())->generate($profile ?? self::profile());
    }

    public static function result(InvocationPlanSource $source, string $text = 'ok'): ModelResult
    {
        return new ModelResult(
            ContractVersion::Version1,
            new ResultIdentity('result.application'),
            $source->request()->identity(),
            $source->request()->profile(),
            ResultMode::Text,
            FinishReason::Completed,
            new ResultText($text),
            null,
            null,
            null,
            ModelVisibility::Private,
        );
    }

    public static function runInput(?InvocationCancellation $cancellation = null, ?InvocationMonotonicClock $clock = null): ModelInvocationApplicationRunInput
    {
        return new ModelInvocationApplicationRunInput(new ReceiptIdentity('receipt.application'), $cancellation ?? new RecordingCancellation(), $clock ?? new RecordingClock());
    }

    private function __construct() {}
}
