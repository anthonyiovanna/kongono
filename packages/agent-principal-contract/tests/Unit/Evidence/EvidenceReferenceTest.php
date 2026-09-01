<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Evidence;

use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use PHPUnit\Framework\TestCase;

final class EvidenceReferenceTest extends TestCase
{
    public function testVocabularyAccessorsAndIdentityEqualityAreExact(): void
    {
        self::assertSame(['authentication', 'authorization', 'delegation', 'consent', 'declaration', 'policy'], array_map(static fn(EvidenceKind $case): string => $case->value, EvidenceKind::cases()));
        $identity = new EvidenceIdentity('delegation.evidence-1');
        $reference = new EvidenceReference(EvidenceKind::Delegation, $identity, str_repeat('a', 64));
        self::assertSame(EvidenceKind::Delegation, $reference->kind());
        self::assertSame($identity, $reference->identity());
        self::assertSame(str_repeat('a', 64), $reference->sha256());
        self::assertTrue($identity->equals(new EvidenceIdentity('delegation.evidence-1')));
    }

    public function testInvalidIdentityAndEveryDigestBoundaryAreNondisclosing(): void
    {
        foreach (['Bad', '1bad', 'bad:value'] as $raw) {
            self::assertRejected(static fn(): EvidenceIdentity => new EvidenceIdentity($raw), $raw, 'KGN-AGENT-CONTRACT-1302');
        }
        foreach ([str_repeat('a', 63), str_repeat('a', 65), str_repeat('A', 64), str_repeat('g', 64), 'secret-digest-value'] as $raw) {
            self::assertRejected(static fn(): EvidenceReference => new EvidenceReference(EvidenceKind::Consent, new EvidenceIdentity('consent'), $raw), $raw, 'KGN-AGENT-CONTRACT-1304');
        }
    }

    /** @param callable(): object $construct */
    private static function assertRejected(callable $construct, string $raw, string $diagnostic): void
    {
        try {
            $construct();
            self::fail('Invalid evidence input was accepted.');
        } catch (InvalidAgentPrincipalContractException $exception) {
            self::assertSame($diagnostic, $exception->diagnosticId());
            self::assertStringNotContainsString($raw, $exception->getMessage());
            self::assertStringNotContainsString($raw, (string) $exception);
        }
    }
}
