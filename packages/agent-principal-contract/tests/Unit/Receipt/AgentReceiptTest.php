<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Receipt;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Proposal\ProposalIdentity;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentPrincipalContract\Receipt\ReceiptIdentity;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use PHPUnit\Framework\TestCase;

final class AgentReceiptTest extends TestCase
{
    public function testOutcomeVocabularyIdentityAndAllAccessorsAreExact(): void
    {
        self::assertSame(['requested', 'proposed', 'authorized', 'attempted', 'performed', 'denied', 'failed', 'cancelled', 'retired'], array_map(static fn(ReceiptOutcome $case): string => $case->value, ReceiptOutcome::cases()));
        $identity = new ReceiptIdentity('receipt.one');
        self::assertTrue($identity->equals(new ReceiptIdentity('receipt.one')));
        self::assertFalse($identity->equals(new ReceiptIdentity('receipt.two')));
        $receipt = self::receipt($identity, ReceiptOutcome::Performed, str_repeat('c', 64));
        self::assertSame($identity, $receipt->identity());
        self::assertSame(PrincipalKind::Agent, $receipt->principal()->reference()->kind());
        self::assertSame('operation.one', $receipt->operation()->value());
        self::assertSame('proposal.one', $receipt->proposalIdentity()->value());
        self::assertSame(ReceiptOutcome::Performed, $receipt->outcome());
        self::assertSame(str_repeat('c', 64), $receipt->evidenceSha256());
        self::assertSame(ProvenanceSource::Capability, $receipt->provenance()->source());
        self::assertSame(AgentVisibility::Application, $receipt->visibility());
    }

    public function testInvalidIdentityAndDigestNeverDiscloseRejectedInput(): void
    {
        foreach (['Receipt', '1receipt', 'receipt:value'] as $raw) {
            self::assertRejected(static fn(): ReceiptIdentity => new ReceiptIdentity($raw), $raw, 'KGN-AGENT-CONTRACT-1302');
        }
        foreach ([str_repeat('a', 63), str_repeat('A', 64), 'secret-receipt-digest'] as $raw) {
            self::assertRejected(static fn(): AgentReceipt => self::receipt(new ReceiptIdentity('receipt'), ReceiptOutcome::Authorized, $raw), $raw, 'KGN-AGENT-CONTRACT-1307');
        }
    }

    private static function receipt(ReceiptIdentity $identity, ReceiptOutcome $outcome, string $digest): AgentReceipt
    {
        return new AgentReceipt(
            $identity,
            new AgentPrincipalReference(new PrincipalReference(PrincipalKind::Agent, new PrincipalId('agent-1'))),
            new OperationReference('operation.one'),
            new ProposalIdentity('proposal.one'),
            $outcome,
            $digest,
            new AgentProvenance(ProvenanceSource::Capability, 'capability.result', str_repeat('d', 64)),
            AgentVisibility::Application,
        );
    }

    /** @param callable(): object $construct */
    private static function assertRejected(callable $construct, string $raw, string $diagnostic): void
    {
        try {
            $construct();
            self::fail('Invalid receipt input was accepted.');
        } catch (InvalidAgentPrincipalContractException $exception) {
            self::assertSame($diagnostic, $exception->diagnosticId());
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
        }
    }
}
