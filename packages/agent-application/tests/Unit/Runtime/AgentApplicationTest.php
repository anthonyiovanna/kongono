<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Tests\Unit\Runtime;

use Kongono\AgentApplication\Exception\AgentApplicationLifecycleException;
use Kongono\AgentApplication\Exception\AgentApplicationOperationAndCleanupException;
use Kongono\AgentApplication\Runtime\AgentApplication;
use Kongono\AgentApplication\Runtime\AgentApplicationRunInput;
use Kongono\AgentApplication\Runtime\AgentApplicationState;
use Kongono\AgentApplication\Tests\Support\AgentApplicationFactory;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Runtime\AgentRuntimeOutcome;
use Kongono\AgentRuntime\Tests\Support\AgentRuntimeFactory;
use Kongono\AgentRuntime\Tests\Support\RecordingAuthorityCurrentness;
use Kongono\AgentRuntime\Tests\Support\RecordingCancellation;
use Kongono\AgentRuntime\Tests\Support\RecordingCapabilityPort;
use Kongono\AgentRuntime\Tests\Support\RecordingClock;
use Kongono\AgentRuntime\Tests\Support\RecordingProposalPort;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AgentApplicationTest extends TestCase
{
    public function testSequentialOperationsDelegateExactlyAndRuntimeOwnsCleanup(): void
    {
        $declaration = AgentApplicationFactory::declaration();
        $application = new AgentApplication(AgentApplicationFactory::profile());
        for ($index = 0; $index < 20; ++$index) {
            $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
            $capability = new RecordingCapabilityPort($declaration->capabilityRequests()[0]->identity(), AgentRuntimeFactory::capabilityResult());
            $result = $application->run(AgentApplicationFactory::runInput($declaration, $proposal, $capability));
            self::assertSame(AgentRuntimeOutcome::Performed, $result->outcome());
            self::assertSame(1, $proposal->proposeCalls);
            self::assertSame(1, $proposal->closeCalls);
            self::assertSame(1, $capability->invokeCalls);
            self::assertSame(1, $capability->closeCalls);
            self::assertFalse($application->hasActiveOperation());
        }
    }

    public function testRuntimeOutcomesAndReceiptMeaningAreReturnedUnchanged(): void
    {
        $declaration = AgentApplicationFactory::declaration();
        $application = new AgentApplication(AgentApplicationFactory::profile());
        $cases = [
            [new RecordingAuthorityCurrentness([false]), new RecordingCancellation(), AgentRuntimeFactory::budget(), AgentRuntimeFactory::capabilityResult(), AgentRuntimeOutcome::AuthorityDenied, null],
            [new RecordingAuthorityCurrentness(), new RecordingCancellation([true]), AgentRuntimeFactory::budget(), AgentRuntimeFactory::capabilityResult(), AgentRuntimeOutcome::Cancelled, null],
            [new RecordingAuthorityCurrentness(), new RecordingCancellation(), AgentRuntimeFactory::budget(['steps' => 1]), AgentRuntimeFactory::capabilityResult(), AgentRuntimeOutcome::BudgetExceeded, null],
            [new RecordingAuthorityCurrentness(), new RecordingCancellation(), AgentRuntimeFactory::budget(), AgentRuntimeFactory::capabilityResult(AgentCapabilityOutcome::Failed), AgentRuntimeOutcome::CapabilityFailed, ReceiptOutcome::Failed],
        ];

        foreach ($cases as [$currentness, $cancellation, $budget, $capabilityResult, $expectedOutcome, $expectedReceipt]) {
            $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
            $capability = new RecordingCapabilityPort($declaration->capabilityRequests()[0]->identity(), $capabilityResult);
            $result = $application->run(new AgentApplicationRunInput(
                AgentRuntimeFactory::operation(),
                AgentRuntimeFactory::intent(),
                $budget,
                $proposal,
                [$capability],
                $currentness,
                $cancellation,
                new RecordingClock(),
            ));

            self::assertSame($expectedOutcome, $result->outcome());
            if ($expectedReceipt instanceof ReceiptOutcome) {
                self::assertContains($expectedReceipt, array_map(static fn($receipt) => $receipt->outcome(), $result->receipts()));
            }
            self::assertSame(1, $proposal->closeCalls);
            self::assertSame(1, $capability->closeCalls);
        }
    }

    public function testReentrantOperationIsRejectedBeforeSecondEffectAndCleansNewPorts(): void
    {
        $declaration = AgentApplicationFactory::declaration();
        $application = new AgentApplication(AgentApplicationFactory::profile());
        $innerProposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
        $innerCapability = new RecordingCapabilityPort($declaration->capabilityRequests()[0]->identity(), AgentRuntimeFactory::capabilityResult());
        $outerProposal = new RecordingProposalPort(function () use ($application, $declaration, $innerProposal, $innerCapability) {
            try {
                $application->close();
                self::fail('Active close must fail.');
            } catch (AgentApplicationLifecycleException) {
                self::assertSame(AgentApplicationState::Open, $application->state());
            }
            try {
                $application->run(AgentApplicationFactory::runInput($declaration, $innerProposal, $innerCapability));
                self::fail('Reentrant operation must fail.');
            } catch (AgentApplicationLifecycleException) {
                self::assertSame(0, $innerProposal->proposeCalls);
            }

            return AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration));
        });
        $outerCapability = new RecordingCapabilityPort($declaration->capabilityRequests()[0]->identity(), AgentRuntimeFactory::capabilityResult());

        $application->run(AgentApplicationFactory::runInput($declaration, $outerProposal, $outerCapability));
        self::assertSame(1, $innerProposal->closeCalls);
        self::assertSame(1, $innerCapability->closeCalls);
        self::assertSame(1, $outerProposal->proposeCalls);
        self::assertSame(1, $outerCapability->invokeCalls);
    }

    public function testPreRuntimeFailureClosesReverseThenProposalAndPreservesAllFailures(): void
    {
        $events = [];
        $record = static function (string $event) use (&$events): void {
            $events[] = $event;
        };
        $profile = AgentApplicationFactory::profile();
        $application = new AgentApplication($profile);
        $proposalFailure = new RuntimeException('proposal-close-secret');
        $capabilityFailure = new RuntimeException('capability-close-secret');
        $laterCapabilityFailure = new RuntimeException('later-capability-close-secret');
        $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(), $proposalFailure, $record);
        $wrongCapability = new RecordingCapabilityPort(AgentRuntimeFactory::capability('wrong.capability')->identity(), AgentRuntimeFactory::capabilityResult(), $capabilityFailure, $record);
        $laterWrongCapability = new RecordingCapabilityPort(AgentRuntimeFactory::capability('wrong.later')->identity(), AgentRuntimeFactory::capabilityResult(), $laterCapabilityFailure, $record);
        $input = new AgentApplicationRunInput(
            AgentRuntimeFactory::operation(),
            AgentRuntimeFactory::intent(),
            AgentRuntimeFactory::budget(),
            $proposal,
            [$wrongCapability, $laterWrongCapability],
            new RecordingAuthorityCurrentness(),
            new RecordingCancellation(),
            new RecordingClock(),
        );

        try {
            $application->run($input);
            self::fail('Invalid port binding must fail.');
        } catch (AgentApplicationOperationAndCleanupException $failure) {
            self::assertSame([$laterCapabilityFailure, $capabilityFailure, $proposalFailure], $failure->cleanupFailures());
            self::assertSame(['wrong.later:close', 'wrong.capability:close', 'proposal:close'], $events);
            self::assertStringNotContainsString('secret', (string) $failure);
        }
        self::assertFalse($application->hasActiveOperation());
    }

    public function testCloseIsTerminalIdempotentAndPostCloseRunCleansPorts(): void
    {
        $declaration = AgentApplicationFactory::declaration();
        $application = new AgentApplication(AgentApplicationFactory::profile());
        $application->close();
        $application->close();
        self::assertSame(AgentApplicationState::Closed, $application->state());
        $proposal = new RecordingProposalPort(AgentRuntimeFactory::proposalResult(AgentRuntimeFactory::proposal($declaration)));
        $capability = new RecordingCapabilityPort($declaration->capabilityRequests()[0]->identity(), AgentRuntimeFactory::capabilityResult(AgentCapabilityOutcome::Performed));

        try {
            $application->run(AgentApplicationFactory::runInput($declaration, $proposal, $capability));
            self::fail('Closed application must reject work.');
        } catch (AgentApplicationLifecycleException) {
            self::assertSame(0, $proposal->proposeCalls);
        }
        self::assertSame(1, $proposal->closeCalls);
        self::assertSame(1, $capability->closeCalls);
    }
}
