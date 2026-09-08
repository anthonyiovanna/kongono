<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Identity;

use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use PHPUnit\Framework\TestCase;

final class EgressIdentityTest extends TestCase
{
    public function testEveryIdentityUsesTheExactBoundaryAndEquality(): void
    {
        foreach ([EgressOperationIdentity::class, EgressAttemptIdentity::class, EgressReceiptIdentity::class] as $type) {
            $one = new $type('a');
            $maximum = new $type('a' . str_repeat('-', 126) . 'z');
            self::assertSame('a', $one->value());
            self::assertTrue($maximum->equals(new $type($maximum->value())));
            self::assertFalse($one->equals($maximum));

            foreach (['', 'A', '-invalid', 'invalid-', 'a/b', str_repeat('a', 129)] as $invalid) {
                try {
                    new $type($invalid);
                    self::fail('Expected identity rejection.');
                } catch (InvalidNetworkEgressContractException $exception) {
                    self::assertSame('KGN-EGRESS-CONTRACT-1501', $exception->diagnosticId());
                    if ('' !== $invalid) {
                        self::assertStringNotContainsString('violation=' . $invalid, (string) $exception);
                    }
                }
            }
        }
    }
}
