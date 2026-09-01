<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Tests\Unit\Capability;

use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use PHPUnit\Framework\TestCase;

final class CapabilityRequestTest extends TestCase
{
    public function testVocabularyValuesAccessorsAndEqualityAreExact(): void
    {
        self::assertSame(['required', 'optional'], array_map(static fn(CapabilityRequirement $case): string => $case->value, CapabilityRequirement::cases()));
        $identity = new CapabilityIdentity('document.read');
        $purpose = new CapabilityPurpose('Read one caller-selected document.');
        $request = new CapabilityRequest($identity, $purpose, CapabilityRequirement::Required);
        self::assertSame($identity, $request->identity());
        self::assertSame($purpose, $request->purpose());
        self::assertSame(CapabilityRequirement::Required, $request->requirement());
        self::assertTrue($identity->equals(new CapabilityIdentity('document.read')));
        self::assertTrue($purpose->equals(new CapabilityPurpose('Read one caller-selected document.')));
    }

    public function testInvalidIdentityAndPurposeAreBoundedAndNondisclosing(): void
    {
        foreach (['', 'Document.Read', 'document/read', 'document..read', 'x' . str_repeat('y', 128)] as $raw) {
            self::assertRejected(static fn(): CapabilityIdentity => new CapabilityIdentity($raw), $raw, 'KGN-AGENT-CONTRACT-1302');
        }
        foreach (['', ' leading', 'trailing ', "line\nbreak", "\xFF", str_repeat('p', 513)] as $raw) {
            self::assertRejected(static fn(): CapabilityPurpose => new CapabilityPurpose($raw), $raw, 'KGN-AGENT-CONTRACT-1303');
        }
    }

    /** @param callable(): object $construct */
    private static function assertRejected(callable $construct, string $raw, string $diagnostic): void
    {
        try {
            $construct();
            self::fail('Invalid capability input was accepted.');
        } catch (InvalidAgentPrincipalContractException $exception) {
            self::assertSame($diagnostic, $exception->diagnosticId());
            if ('' !== $raw) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
            }
        }
    }
}
