<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Tests\Support;

use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
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
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfileFactory;
use Kongono\ModelProviderOpenAiResponses\Tests\Support\OpenAiResponsesAdapterFixture;

final class OpenAiResponsesInspectionFactory
{
    public const string SECRET = 'raw-secret-prompt.example/private';
    public const string RESULT_SECRET = 'raw-private-result-payload';

    public static function fixture(string $suffix = 'inspection', bool $streaming = false): OpenAiResponsesAdapterFixture
    {
        return new OpenAiResponsesAdapterFixture(streaming: $streaming, identitySuffix: '.' . $suffix);
    }

    public static function profile(?OpenAiResponsesAdapterFixture $fixture = null): OpenAiResponsesApplicationProfile
    {
        $fixture ??= self::fixture();
        $model = (new ModelInvocationApplicationProfileFactory())->create($fixture->source, $fixture->artifactBytes);

        return (new OpenAiResponsesApplicationProfileFactory())->create($fixture->openAiProfile, $model, $fixture->profile());
    }

    public static function runtime(OpenAiResponsesAdapterFixture $fixture, InvocationOutcome $outcome = InvocationOutcome::Succeeded): InvocationRuntimeResult
    {
        $request = $fixture->source->request();
        $usage = InvocationOutcome::Succeeded === $outcome
            ? new UsageEvidence([new UsageQuantity(new UsageMetricName('tokens'), new UsageUnitName('count'), 7)])
            : null;
        $result = InvocationOutcome::Succeeded === $outcome ? new ModelResult(
            ContractVersion::Version1,
            new ResultIdentity('result.openai.inspection'),
            $request->identity(),
            $request->profile(),
            $request->resultMode(),
            FinishReason::Completed,
            new ResultText(self::RESULT_SECRET),
            null,
            $usage,
            null,
            ModelVisibility::Private,
        ) : null;
        $receipt = new InvocationReceipt(
            new ReceiptIdentity('receipt.openai.inspection'),
            $request->identity(),
            $request->profile(),
            $outcome,
            InvocationOutcome::Rejected === $outcome ? 0 : 1,
            $result?->identity(),
            $usage,
            null,
            true,
        );
        $events = InvocationOutcome::Succeeded === $outcome ? [
            new ModelStreamEvent($request->identity(), new StreamSequence(1), StreamEventKind::Started),
            new ModelStreamEvent($request->identity(), new StreamSequence(2), StreamEventKind::ContentDelta, self::RESULT_SECRET),
            new ModelStreamEvent($request->identity(), new StreamSequence(3), StreamEventKind::Usage, usage: $usage),
            new ModelStreamEvent($request->identity(), new StreamSequence(4), StreamEventKind::Completed),
        ] : [];

        return new InvocationRuntimeResult($receipt, $result, $events, InvocationState::Retired, 12, 48);
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

    private function __construct() {}
}
