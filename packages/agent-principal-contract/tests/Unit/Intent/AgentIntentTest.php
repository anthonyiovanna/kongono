<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Intent;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Intent\IntentIdentity;
use Kongono\AgentPrincipalContract\Intent\IntentPurpose;
use PHPUnit\Framework\TestCase;

final class AgentIntentTest extends TestCase
{
    public function testIntentPreservesExactImmutableValuesAndEquality(): void
    {
        $identity = new IntentIdentity('review.change-1');
        $purpose = new IntentPurpose('Review one bounded application change.');
        $intent = new AgentIntent($identity, $purpose);
        self::assertSame($identity, $intent->identity());
        self::assertSame($purpose, $intent->purpose());
        self::assertTrue($identity->equals(new IntentIdentity('review.change-1')));
        self::assertTrue($purpose->equals(new IntentPurpose('Review one bounded application change.')));
    }

    public function testIdentityAndPurposeBoundariesFailWithoutDisclosure(): void
    {
        foreach (['', 'Upper', '1start', 'bad value', 'a' . str_repeat('b', 128)] as $invalid) {
            self::assertRejected(static fn(): IntentIdentity => new IntentIdentity($invalid), $invalid, 'KGN-AGENT-CONTRACT-1302');
        }

        self::assertSame(str_repeat('é', 256), (new IntentPurpose(str_repeat('é', 256)))->value());
        foreach (['', ' leading', 'trailing ', "line\nbreak", "carriage\rreturn", "nul\0byte", "control\u{0085}", "\xC3\x28", str_repeat('a', 513)] as $invalid) {
            self::assertRejected(static fn(): IntentPurpose => new IntentPurpose($invalid), $invalid, 'KGN-AGENT-CONTRACT-1303');
        }
    }

    /** @param callable(): object $construct */
    private static function assertRejected(callable $construct, string $raw, string $diagnostic): void
    {
        try {
            $construct();
            self::fail('Invalid intent input was accepted.');
        } catch (InvalidAgentPrincipalContractException $exception) {
            self::assertSame($diagnostic, $exception->diagnosticId());
            if ('' !== $raw) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
