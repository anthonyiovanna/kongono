<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Policy;

use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicySourceException;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EgressPolicyIdentityTest extends TestCase
{
    public function testMinimumMaximumAndExactEquality(): void
    {
        $minimum = new EgressPolicyIdentity('a');
        $maximum = new EgressPolicyIdentity('a' . str_repeat('b', 126) . 'c');
        self::assertSame('a', $minimum->value());
        self::assertSame(128, strlen($maximum->value()));
        self::assertTrue($minimum->equals(new EgressPolicyIdentity('a')));
        self::assertFalse($minimum->equals(new EgressPolicyIdentity('b')));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentities(): iterable
    {
        foreach (['', str_repeat('a', 129), '.leading', 'trailing-', 'UPPER', 'has space', "control\n", 'nonascii-é', 'tenant:policy'] as $position => $value) {
            yield 'invalid-' . $position => [$value];
        }
    }

    #[DataProvider('invalidIdentities')]
    public function testInvalidGrammarIsRejectedWithoutNormalization(string $value): void
    {
        $this->expectException(InvalidEgressPolicySourceException::class);
        new EgressPolicyIdentity($value);
    }
}
