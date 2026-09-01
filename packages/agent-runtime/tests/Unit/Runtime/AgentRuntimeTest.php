<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Unit\Runtime;

use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Intent\IntentIdentity;
use Kongono\AgentPrincipalContract\Intent\IntentPurpose;
use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Proposal\ProposalIdentity;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationAndCleanupException;
use Kongono\AgentRuntime\Exception\AgentRuntimeOperationException;
use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\AgentRuntime\Runtime\AgentRuntime;
use Kongono\AgentRuntime\Runtime\AgentRuntimeOutcome;
use Kongono\AgentRuntime\Runtime\AgentRuntimeRequest;
use Kongono\AgentRuntime\Runtime\AgentRuntimeState;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use Kongono\AgentRuntime\Tests\Support\RecordingAuthorityCurrentness;
use Kongono\AgentRuntime\Tests\Support\RecordingCancellation;
use Kongono\AgentRuntime\Tests\Support\RecordingCapabilityPort;
use Kongono\AgentRuntime\Tests\Support\RecordingClock;
use Kongono\AgentRuntime\Tests\Support\RecordingProposalPort;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentRuntimeTest extends TestCase
{
    public function testExactAdmittedOperationPerformsOnceAndProducesDeterministicEvidence(): void
    {
        [$request, $proposalPort, $capabilityPort] = self::fixture();
        $first = (new AgentRuntime())->run($request);
        [$secondRequest] = self::fixture();
        $second = (new AgentRuntime())->run($secondRequest);

        self::assertSame(AgentRuntimeOutcome::Performed, $first->outcome());
        self::assertSame(AgentRuntimeState::Retired, $first->terminalState());
        self::assertSame(1, $proposalPort->proposeCalls);
        self::assertSame(1, $capabilityPort->invokeCalls);
        self::assertTrue($first->cleanupCompletedWithoutFailure());
        self::assertSame(
            [ReceiptOutcome::Proposed, ReceiptOutcome::Authorized, ReceiptOutcome::Attempted, ReceiptOutcome::Performed, ReceiptOutcome::Retired],
            array_map(static fn($receipt) => $receipt->outcome(), $first->receipts()),
        );
        self::assertSame(array_map(static fn($receipt) => $receipt->identity()->value(), $first->receipts()), array_map(static fn($receipt) => $receipt->identity()->value(), $second->receipts()));
        self::assertSame(array_map(static fn($receipt) => $receipt->evidenceSha256(), $first->receipts()), array_map(static fn($receipt) => $receipt->evidenceSha256(), $second->receipts()));
    }

    public function testEveryCapabilityPortClosesOnceInReverseOrderThenProposalCloses(): void
    {
        $events = [];
        $record = static function (string $event) use (&$events): void {
            $events[] = $event;
        };
        $declaration = AgentRuntimeFactory::declaration([
            AgentRuntimeFactory::capability('calendar.read'),
            AgentRuntimeFactory::capability('network.read'),
        ]);
        $proposalPort = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)), record: $record);
        $calendar = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), AgentRuntimeFactory::capabilityResult(), record: $record);
        $network = new RecordingCapabilityPort(new CapabilityIdentity('network.read'), AgentRuntimeFactory::capabilityResult(), record: $record);
        $request = self::request($declaration, AgentRuntimeFactory::plan($declaration), $proposalPort, [$calendar, $network]);

        (new AgentRuntime())->run($request);

        self::assertSame(1, $calendar->invokeCalls);
        self::assertSame(0, $network->invokeCalls);
        self::assertSame(1, $calendar->closeCalls);
        self::assertSame(1, $network->closeCalls);
        self::assertSame(['proposal:propose', 'calendar.read:invoke', 'network.read:close', 'calendar.read:close', 'proposal:close'], $events);
    }

    public function testDeniedAndMismatchedPlansOpenNoEffectAndStillCleanupAcceptedPorts(): void
    {
        $declaration = AgentRuntimeFactory::declaration();
        $admitted = AgentRuntimeFactory::plan($declaration);
        $denied = new AgentAuthorityPlan(
            $admitted->identity(),
            $admitted->declarationIdentity(),
            $admitted->declarationSha256(),
            $admitted->principal(),
            $admitted->authenticationOutcome(),
            AgentAdmissionDecision::Denied,
            $admitted->capabilityDecisions(),
            $admitted->constraints(),
            $admitted->evidenceReferences(),
        );
        $proposalPort = new RecordingProposalPort(AgentRuntimeFactory::proposalResult());
        $capabilityPort = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), AgentRuntimeFactory::capabilityResult());
        $request = self::request($declaration, $denied, $proposalPort, [$capabilityPort]);

        try {
            (new AgentRuntime())->run($request);
            self::fail('Denied plan must be invalid runtime input.');
        } catch (InvalidAgentRuntimeException) {
        }
        self::assertSame(0, $proposalPort->proposeCalls);
        self::assertSame(0, $capabilityPort->invokeCalls);
        self::assertSame(1, $proposalPort->closeCalls);
        self::assertSame(1, $capabilityPort->closeCalls);

        $other = AgentRuntimeFactory::declaration(principalId: 'agent-other');
        $proposalPort = new RecordingProposalPort(AgentRuntimeFactory::proposalResult());
        $request = self::request($declaration, AgentRuntimeFactory::plan($other), $proposalPort, []);
        $this->expectException(InvalidAgentRuntimeException::class);
        (new AgentRuntime())->run($request);
    }

    public function testAuthorityCurrentnessDeniesBeforeEachPossibleEffect(): void
    {
        [$request, $proposal, $capability] = self::fixture(currentness: new RecordingAuthorityCurrentness([false]));
        $result = (new AgentRuntime())->run($request);
        self::assertSame(AgentRuntimeOutcome::AuthorityDenied, $result->outcome());
        self::assertSame(0, $proposal->proposeCalls);
        self::assertSame(0, $capability->invokeCalls);

        [$request, $proposal, $capability] = self::fixture(currentness: new RecordingAuthorityCurrentness([true, false]));
        $result = (new AgentRuntime())->run($request);
        self::assertSame(AgentRuntimeOutcome::AuthorityDenied, $result->outcome());
        self::assertSame(1, $proposal->proposeCalls);
        self::assertSame(0, $capability->invokeCalls);
    }

    /** @return iterable<string, array{non-empty-list<bool>,int,int,bool}> */
    public static function cancellationCheckpoints(): iterable
    {
        yield 'before proposal' => [[true], 0, 0, false];
        yield 'after proposal' => [[false, true], 1, 0, false];
        yield 'before capability' => [[false, false, true], 1, 0, false];
        yield 'after capability' => [[false, false, false, true], 1, 1, true];
    }

    /** @param non-empty-list<bool> $observations */
    #[DataProvider('cancellationCheckpoints')]
    public function testCancellationAtEveryCheckpointPreventsLaterEffects(array $observations, int $proposalCalls, int $capabilityCalls, bool $performedPreserved): void
    {
        [$request, $proposal, $capability] = self::fixture(cancellation: new RecordingCancellation($observations));
        $result = (new AgentRuntime())->run($request);
        self::assertSame(AgentRuntimeOutcome::Cancelled, $result->outcome());
        self::assertSame($proposalCalls, $proposal->proposeCalls);
        self::assertSame($capabilityCalls, $capability->invokeCalls);
        $outcomes = array_map(static fn($receipt) => $receipt->outcome(), $result->receipts());
        self::assertSame($performedPreserved, in_array(ReceiptOutcome::Performed, $outcomes, true));
        if ($proposalCalls > 0) {
            self::assertContains(ReceiptOutcome::Cancelled, $outcomes);
        }
    }

    public function testNegativeAndDecreasingClockValuesFailSafelyAfterCleanup(): void
    {
        foreach ([[ -1 ], [10, 9]] as $values) {
            [$request, $proposal, $capability] = self::fixture(clock: new RecordingClock($values));
            try {
                (new AgentRuntime())->run($request);
                self::fail('Invalid clock must fail.');
            } catch (AgentRuntimeOperationException $failure) {
                self::assertSame('KGN-AGENT-RUNTIME-1331', $failure->diagnosticId());
            }
            self::assertSame(1, $proposal->closeCalls);
            self::assertSame(1, $capability->closeCalls);
        }
    }

    public function testDurationBoundaryAndPlusOneAreDistinct(): void
    {
        [$exact] = self::fixture(clock: new RecordingClock([0, 0, 100, 100, 100]));
        self::assertSame(AgentRuntimeOutcome::Performed, (new AgentRuntime())->run($exact)->outcome());
        [$over, , $capability] = self::fixture(clock: new RecordingClock([0, 0, 101]));
        self::assertSame(AgentRuntimeOutcome::BudgetExceeded, (new AgentRuntime())->run($over)->outcome());
        self::assertSame(0, $capability->invokeCalls);
    }

    public function testStepTokenCapabilityAndOutputBoundariesStopOnlyTheNextEffect(): void
    {
        [$step, , $capability] = self::fixture(budget: AgentRuntimeFactory::budget(['steps' => 1]));
        self::assertSame(AgentRuntimeOutcome::BudgetExceeded, (new AgentRuntime())->run($step)->outcome());
        self::assertSame(0, $capability->invokeCalls);

        [$tokenExact] = self::fixture(budget: AgentRuntimeFactory::budget(['tokens' => 5]));
        self::assertSame(AgentRuntimeOutcome::Performed, (new AgentRuntime())->run($tokenExact)->outcome());
        [$tokenOver, , $capability] = self::fixture(budget: AgentRuntimeFactory::budget(['tokens' => 4]));
        self::assertSame(AgentRuntimeOutcome::BudgetExceeded, (new AgentRuntime())->run($tokenOver)->outcome());
        self::assertSame(0, $capability->invokeCalls);

        [$call, , $capability] = self::fixture(budget: AgentRuntimeFactory::budget(['capability_calls' => 0]));
        self::assertSame(AgentRuntimeOutcome::BudgetExceeded, (new AgentRuntime())->run($call)->outcome());
        self::assertSame(0, $capability->invokeCalls);

        [$outputExact] = self::fixture(budget: AgentRuntimeFactory::budget(['output' => 5]));
        self::assertSame(AgentRuntimeOutcome::Performed, (new AgentRuntime())->run($outputExact)->outcome());
        [$outputOver] = self::fixture(budget: AgentRuntimeFactory::budget(['output' => 4]));
        $result = (new AgentRuntime())->run($outputOver);
        self::assertSame(AgentRuntimeOutcome::BudgetExceeded, $result->outcome());
        self::assertContains(ReceiptOutcome::Performed, array_map(static fn($receipt) => $receipt->outcome(), $result->receipts()));
    }

    public function testMissingConstraintIsZeroCeilingAndConcurrencyBelowOneRejectsBeforeEffect(): void
    {
        $constraints = array_values(array_filter(AgentRuntimeFactory::constraints(), static fn(AgentConstraint $constraint): bool => ConstraintKind::TokenLimit !== $constraint->kind()));
        $declaration = AgentRuntimeFactory::declaration(constraints: $constraints);
        $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
        $capability = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), AgentRuntimeFactory::capabilityResult());
        $request = self::request($declaration, AgentRuntimeFactory::plan($declaration), $proposal, [$capability], budget: AgentRuntimeFactory::budget(['tokens' => 1]));
        try {
            (new AgentRuntime())->run($request);
            self::fail('Missing constraint must be a zero ceiling.');
        } catch (InvalidAgentRuntimeException) {
        }
        self::assertSame(0, $proposal->proposeCalls);

        [$request, $proposal] = self::fixture(budget: AgentRuntimeFactory::budget(['concurrency' => 0]));
        try {
            (new AgentRuntime())->run($request);
            self::fail('Concurrency zero must reject.');
        } catch (InvalidAgentRuntimeException) {
        }
        self::assertSame(0, $proposal->proposeCalls);
    }

    public function testRetryDelegationAndRetainedUsageRemainZeroWhileConcurrencyIsOne(): void
    {
        [$request] = self::fixture();
        $usage = (new AgentRuntime())->run($request)->usage();
        self::assertSame(0, $usage->retries());
        self::assertSame(0, $usage->delegationDepth());
        self::assertSame(0, $usage->retainedStateBytes());
        self::assertSame(1, $usage->concurrency());
        self::assertSame(2, $usage->steps());
        self::assertSame(1, $usage->capabilityCalls());
    }

    public function testInvalidCapabilityPortCollectionsRejectBeforeRuntimeOwnership(): void
    {
        $declaration = AgentRuntimeFactory::declaration([
            AgentRuntimeFactory::capability('calendar.read'), AgentRuntimeFactory::capability('network.read'),
        ]);
        $plan = AgentRuntimeFactory::plan($declaration);
        $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
        $calendar = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), AgentRuntimeFactory::capabilityResult());
        $network = new RecordingCapabilityPort(new CapabilityIdentity('network.read'), AgentRuntimeFactory::capabilityResult());
        foreach ([[$calendar, $calendar], [$network, $calendar], [$calendar, new \stdClass()]] as $ports) {
            try {
                self::request($declaration, $plan, $proposal, $ports);
                self::fail('Invalid port collection must reject.');
            } catch (InvalidAgentRuntimeException) {
            }
        }

        $extraneous = new RecordingCapabilityPort(new CapabilityIdentity('process.open'), AgentRuntimeFactory::capabilityResult());
        $this->expectException(InvalidAgentRuntimeException::class);
        self::request($declaration, $plan, $proposal, [$extraneous]);
    }

    public function testProposalMismatchesRejectBeforeCapability(): void
    {
        $declaration = AgentRuntimeFactory::declaration();
        $operation = AgentRuntimeFactory::operation();
        $intent = AgentRuntimeFactory::intent();
        $base = AgentRuntimeFactory::proposal($declaration, $operation, $intent);
        $bad = [
            self::proposal($declaration, operation: new OperationReference('operation.other')),
            self::proposal($declaration, intent: new AgentIntent(new IntentIdentity('intent.other'), new IntentPurpose('Perform one bounded operation'))),
            self::proposal($declaration, capability: new CapabilityRequest(new CapabilityIdentity('calendar.read'), new CapabilityPurpose('Different bounded purpose'), CapabilityRequirement::Required)),
            self::proposal($declaration, constraints: []),
            self::proposal($declaration, evidence: [new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.other'), str_repeat('1', 64))]),
            self::proposal($declaration, provenance: new AgentProvenance(ProvenanceSource::Application, 'application.other', str_repeat('2', 64))),
            self::proposal($declaration, visibility: AgentVisibility::Private),
        ];
        self::assertInstanceOf(ActionProposal::class, $base);
        foreach ($bad as $proposalValue) {
            $proposalPort = new RecordingProposalPort(AgentRuntimeFactory::proposalResult($proposalValue));
            $capability = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), AgentRuntimeFactory::capabilityResult());
            $request = self::request($declaration, AgentRuntimeFactory::plan($declaration), $proposalPort, [$capability]);
            try {
                (new AgentRuntime())->run($request);
                self::fail('Proposal mismatch must reject.');
            } catch (InvalidAgentRuntimeException) {
            }
            self::assertSame(0, $capability->invokeCalls);
        }
    }

    public function testCapabilityFailedRemainsDistinctFromPerformed(): void
    {
        [$request] = self::fixture(capabilityResult: AgentRuntimeFactory::capabilityResult(AgentCapabilityOutcome::Failed));
        $result = (new AgentRuntime())->run($request);
        self::assertSame(AgentRuntimeOutcome::CapabilityFailed, $result->outcome());
        self::assertSame(AgentCapabilityOutcome::Failed, $result->capabilityResult()?->outcome());
        self::assertContains(ReceiptOutcome::Failed, array_map(static fn($receipt) => $receipt->outcome(), $result->receipts()));
        self::assertNotContains(ReceiptOutcome::Performed, array_map(static fn($receipt) => $receipt->outcome(), $result->receipts()));
    }

    public function testPrimaryAndMultipleCleanupFailuresRemainDistinguishable(): void
    {
        $primary = new RuntimeException('sensitive-primary');
        $calendarCleanup = new RuntimeException('sensitive-calendar-cleanup');
        $networkCleanup = new RuntimeException('sensitive-network-cleanup');
        $proposalCleanup = new RuntimeException('sensitive-proposal-cleanup');
        $declaration = AgentRuntimeFactory::declaration([
            AgentRuntimeFactory::capability('calendar.read'), AgentRuntimeFactory::capability('network.read'),
        ]);
        $proposal = new RecordingProposalPort($primary, $proposalCleanup);
        $calendar = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), AgentRuntimeFactory::capabilityResult(), $calendarCleanup);
        $network = new RecordingCapabilityPort(new CapabilityIdentity('network.read'), AgentRuntimeFactory::capabilityResult(), $networkCleanup);
        $request = self::request($declaration, AgentRuntimeFactory::plan($declaration), $proposal, [$calendar, $network]);

        try {
            (new AgentRuntime())->run($request);
            self::fail('Combined lifecycle failure expected.');
        } catch (AgentRuntimeOperationAndCleanupException $failure) {
            self::assertInstanceOf(AgentRuntimeOperationException::class, $failure->primaryFailure());
            self::assertSame($primary, $failure->primaryFailure()->getPrevious());
            self::assertSame([$networkCleanup, $calendarCleanup, $proposalCleanup], $failure->cleanupFailures());
            self::assertStringNotContainsString('sensitive', (string) $failure);
        }
    }

    /**
     * @return array{AgentRuntimeRequest,RecordingProposalPort,RecordingCapabilityPort}
     */
    private static function fixture(
        ?RecordingAuthorityCurrentness $currentness = null,
        ?RecordingCancellation $cancellation = null,
        ?RecordingClock $clock = null,
        ?AgentRuntimeBudget $budget = null,
        ?\Kongono\AgentRuntime\Capability\AgentCapabilityResult $capabilityResult = null,
    ): array {
        $declaration = AgentRuntimeFactory::declaration();
        $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
        $capability = new RecordingCapabilityPort(new CapabilityIdentity('calendar.read'), $capabilityResult ?? AgentRuntimeFactory::capabilityResult());
        return [
            self::request($declaration, AgentRuntimeFactory::plan($declaration), $proposal, [$capability], $currentness, $cancellation, $clock, $budget),
            $proposal,
            $capability,
        ];
    }

    /** @param array<array-key, mixed> $ports */
    private static function request(
        AgentDeclaration $declaration,
        AgentAuthorityPlan $plan,
        RecordingProposalPort $proposal,
        array $ports,
        ?RecordingAuthorityCurrentness $currentness = null,
        ?RecordingCancellation $cancellation = null,
        ?RecordingClock $clock = null,
        ?AgentRuntimeBudget $budget = null,
    ): AgentRuntimeRequest {
        return new AgentRuntimeRequest(
            $declaration,
            $plan,
            AgentRuntimeFactory::operation(),
            AgentRuntimeFactory::intent(),
            $budget ?? AgentRuntimeFactory::budget(),
            $proposal,
            $ports,
            $currentness ?? new RecordingAuthorityCurrentness(),
            $cancellation ?? new RecordingCancellation(),
            $clock ?? new RecordingClock(),
        );
    }

    /**
     * @param list<AgentConstraint>|null $constraints
     * @param list<EvidenceReference>|null $evidence
     */
    private static function proposal(
        AgentDeclaration $declaration,
        ?OperationReference $operation = null,
        ?AgentIntent $intent = null,
        ?CapabilityRequest $capability = null,
        ?array $constraints = null,
        ?array $evidence = null,
        ?AgentProvenance $provenance = null,
        ?AgentVisibility $visibility = null,
    ): ActionProposal {
        return new ActionProposal(
            new ProposalIdentity('proposal.primary'),
            $declaration->principal(),
            $operation ?? AgentRuntimeFactory::operation(),
            $intent ?? AgentRuntimeFactory::intent(),
            $capability ?? $declaration->capabilityRequests()[0],
            $constraints ?? $declaration->constraints(),
            $evidence ?? $declaration->evidenceReferences(),
            $provenance ?? $declaration->provenance(),
            $visibility ?? $declaration->visibility(),
        );
    }
}
