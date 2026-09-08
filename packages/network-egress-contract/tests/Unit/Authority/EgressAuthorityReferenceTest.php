<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Authority;

use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Policy\EgressPolicyReference;
use PHPUnit\Framework\TestCase;

final class EgressAuthorityReferenceTest extends TestCase
{
    public function testAuthorityAndPolicyReferencesAreExactInertEvidence(): void
    {
        $authority = new EgressAuthorityReference('authority.1', 7);
        self::assertSame('authority.1', $authority->identity());
        self::assertSame(7, $authority->revision());
        self::assertTrue($authority->equals(new EgressAuthorityReference('authority.1', 7)));

        $digest = str_repeat('A', 64);
        $policy = new EgressPolicyReference('policy_1', $digest);
        self::assertSame('policy_1', $policy->identity());
        self::assertSame($digest, $policy->sha256());
        self::assertTrue($policy->equals(new EgressPolicyReference('policy_1', $digest)));
    }

    public function testReferenceBoundariesFailClosedWithoutDisclosure(): void
    {
        foreach (['a', 'a' . str_repeat('-', 126) . 'z'] as $identity) {
            self::assertSame($identity, (new EgressAuthorityReference($identity, 1))->identity());
            self::assertSame($identity, (new EgressPolicyReference($identity, str_repeat('A', 64)))->identity());
        }

        foreach (['', 'A', '-invalid', 'invalid-', 'a/b', str_repeat('a', 129)] as $identity) {
            foreach (['authority', 'policy'] as $reference) {
                try {
                    if ('authority' === $reference) {
                        new EgressAuthorityReference($identity, 1);
                    } else {
                        new EgressPolicyReference($identity, str_repeat('A', 64));
                    }
                    self::fail('Expected reference identity rejection.');
                } catch (InvalidNetworkEgressContractException $exception) {
                    self::assertSame('KGN-EGRESS-CONTRACT-1501', $exception->diagnosticId());
                    if ('' !== $identity) {
                        self::assertStringNotContainsString('violation=' . $identity, (string) $exception);
                    }
                }
            }
        }

        foreach ([0, -1] as $revision) {
            try {
                new EgressAuthorityReference('authority', $revision);
                self::fail('Expected revision rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1503', $exception->diagnosticId());
            }
        }

        foreach ([str_repeat('A', 63), str_repeat('A', 65), str_repeat('a', 64), str_repeat('G', 64)] as $digest) {
            try {
                new EgressPolicyReference('policy', $digest);
                self::fail('Expected digest rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1503', $exception->diagnosticId());
                self::assertStringNotContainsString($digest, (string) $exception);
            }
        }
    }
}
