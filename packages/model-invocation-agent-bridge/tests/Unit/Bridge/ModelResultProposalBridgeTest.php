<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Unit\Bridge;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ModelInvocationAgentBridge\Bridge\ModelResultProposalBridge;
use Kongono\ModelInvocationAgentBridge\Exception\ProposalBridgeRejectedException;
use Kongono\ModelInvocationAgentBridge\Internal\CanonicalEvidence;
use Kongono\ModelInvocationAgentBridge\Tests\Fixture\BridgeFixture;
use Kongono\ModelInvocationAgentBridge\Tests\Fixture\RuntimeResultFactory;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ModelResultProposalBridge::class)]
final class ModelResultProposalBridgeTest extends TestCase
{
    public function testItCreatesExactDeterministicInertProposalEvidence(): void
    {
        $request = BridgeFixture::request();
        $mapping = BridgeFixture::mapping();
        $runtimeResult = RuntimeResultFactory::action();
        $result = (new ModelResultProposalBridge())->bridge($request, $runtimeResult, $mapping);
        $proposal = $result->proposal();

        self::assertSame($request->principal(), $proposal->principal());
        self::assertSame($request->operation(), $proposal->operation());
        self::assertSame($request->intent(), $proposal->intent());
        self::assertSame($request->constraints()[0], $proposal->constraints()[0]);
        self::assertSame($request->evidenceReferences()[0], $proposal->evidenceReferences()[0]);
        self::assertSame($mapping->capabilityRequest(), $proposal->capabilityRequest());
        self::assertSame(7, $result->tokenUsage());
        self::assertSame(strlen('calendar.read') + strlen('{"day":"monday"}'), $result->outputByteUsage());
        self::assertSame(AgentVisibility::Private, $proposal->visibility());
        self::assertSame(ProvenanceSource::Model, $proposal->provenance()->source());
        self::assertSame('result.primary', $proposal->provenance()->sourceIdentity());
        self::assertSame($result->evidenceSha256(), $proposal->provenance()->sha256());
        self::assertSame('model-proposal.' . $result->evidenceSha256(), $proposal->identity()->value());

        $modelResult = $runtimeResult->result();
        self::assertNotNull($modelResult);
        $bytes = CanonicalEvidence::bytes($modelResult, $mapping, 7, $result->outputByteUsage(), AgentVisibility::Private);
        self::assertSame(hash('sha256', $bytes), $result->evidenceSha256());
        self::assertSame($result->evidenceSha256(), (new ModelResultProposalBridge())->bridge($request, $runtimeResult, $mapping)->evidenceSha256());
    }

    public function testArgumentChangesAlterOnlyDigestAndByteEvidence(): void
    {
        $bridge = new ModelResultProposalBridge();
        $request = BridgeFixture::request();
        $mapping = BridgeFixture::mapping();
        $first = $bridge->bridge($request, RuntimeResultFactory::action('{"value":"alpha"}'), $mapping);
        $second = $bridge->bridge($request, RuntimeResultFactory::action('{"value":"beta"}'), $mapping);

        self::assertNotSame($first->evidenceSha256(), $second->evidenceSha256());
        self::assertSame($first->proposal()->capabilityRequest(), $mapping->capabilityRequest());
        self::assertSame($second->proposal()->capabilityRequest(), $mapping->capabilityRequest());
        self::assertStringNotContainsString('alpha', $first->proposal()->identity()->value());
        self::assertStringNotContainsString('beta', $second->proposal()->identity()->value());
    }

    public function testActionAndCapabilityMatchingAreExactAndCaseSensitive(): void
    {
        $bridge = new ModelResultProposalBridge();

        try {
            $bridge->bridge(BridgeFixture::request(), RuntimeResultFactory::action(action: 'calendar.write'), BridgeFixture::mapping());
            self::fail('Expected exact action mismatch rejection.');
        } catch (ProposalBridgeRejectedException $exception) {
            self::assertSame('KGN-MODEL-BRIDGE-1427', $exception->diagnosticId());
        }

        $this->expectException(ProposalBridgeRejectedException::class);
        $bridge->bridge(BridgeFixture::request([new CapabilityIdentity('calendar.write')]), RuntimeResultFactory::action(), BridgeFixture::mapping());
    }

    public function testUsageSelectionUsesOneExactMetricAndUnitWithoutFallbackOrSummation(): void
    {
        $quantities = [
            new UsageQuantity(new UsageMetricName('input_tokens'), new UsageUnitName('count'), 3),
            new UsageQuantity(new UsageMetricName('tokens'), new UsageUnitName('count'), 11),
        ];
        $result = (new ModelResultProposalBridge())->bridge(
            BridgeFixture::request(),
            RuntimeResultFactory::action(quantities: $quantities),
            BridgeFixture::mapping(),
        );
        self::assertSame(11, $result->tokenUsage());

        $this->expectException(ProposalBridgeRejectedException::class);
        (new ModelResultProposalBridge())->bridge(
            BridgeFixture::request(),
            RuntimeResultFactory::action(quantities: $quantities),
            BridgeFixture::mapping(metric: 'token'),
        );
    }

    #[DataProvider('ineligibleRuntimeProvider')]
    public function testIneligibleRuntimeOutcomesFailClosed(InvocationOutcome $outcome): void
    {
        $this->expectException(ProposalBridgeRejectedException::class);
        (new ModelResultProposalBridge())->bridge(
            BridgeFixture::request(),
            RuntimeResultFactory::unsuccessful($outcome),
            BridgeFixture::mapping(),
        );
    }

    /** @return iterable<string, array{InvocationOutcome}> */
    public static function ineligibleRuntimeProvider(): iterable
    {
        yield 'failed' => [InvocationOutcome::Failed];
        yield 'cancelled' => [InvocationOutcome::Cancelled];
        yield 'rejected' => [InvocationOutcome::Rejected];
    }

    public function testTextResultsFailClosed(): void
    {
        $this->expectException(ProposalBridgeRejectedException::class);
        (new ModelResultProposalBridge())->bridge(BridgeFixture::request(), RuntimeResultFactory::text(), BridgeFixture::mapping());
    }

    #[DataProvider('visibilityProvider')]
    public function testVisibilityProjectsOnlyToTheSameNamedAgentVisibility(ModelVisibility $model, AgentVisibility $agent): void
    {
        $result = (new ModelResultProposalBridge())->bridge(BridgeFixture::request(), RuntimeResultFactory::action(visibility: $model), BridgeFixture::mapping());

        self::assertSame($agent, $result->proposal()->visibility());
    }

    /** @return iterable<string, array{ModelVisibility, AgentVisibility}> */
    public static function visibilityProvider(): iterable
    {
        yield 'private' => [ModelVisibility::Private, AgentVisibility::Private];
        yield 'protected' => [ModelVisibility::Protected, AgentVisibility::Protected];
        yield 'application' => [ModelVisibility::Application, AgentVisibility::Application];
    }

    public function testSequentialInstancesAndReuseRetainNoCallState(): void
    {
        $bridge = new ModelResultProposalBridge();
        $request = BridgeFixture::request();
        $mapping = BridgeFixture::mapping();

        $first = $bridge->bridge($request, RuntimeResultFactory::action('{"sequence":1}'), $mapping);
        $nested = (new ModelResultProposalBridge())->bridge($request, RuntimeResultFactory::action('{"sequence":2}'), $mapping);
        $third = $bridge->bridge($request, RuntimeResultFactory::action('{"sequence":1}'), $mapping);

        self::assertNotSame($first->evidenceSha256(), $nested->evidenceSha256());
        self::assertSame($first->evidenceSha256(), $third->evidenceSha256());
    }
}
