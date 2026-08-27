<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit;

use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use PHPUnit\Framework\TestCase;

final class AuthenticationValueTest extends TestCase
{
    public function testMechanismAcceptsOnlyExactCanonicalGrammarAndEquality(): void
    {
        foreach (['a', 'mechanism.v1', 'step:level-2', 'app_mechanism'] as $value) {
            self::assertSame($value, (new AuthenticationMechanism($value))->value());
        }

        $mechanism = new AuthenticationMechanism('synthetic.v1');
        self::assertTrue($mechanism->equals(new AuthenticationMechanism('synthetic.v1')));
        self::assertFalse($mechanism->equals(new AuthenticationMechanism('synthetic.v2')));
    }

    public function testMechanismRejectsInvalidValuesWithoutNormalizationOrDisclosure(): void
    {
        foreach (['', ' Upper', 'upper ', 'Upper', 'a..b', 'a/', 'a' . str_repeat('b', 64)] as $value) {
            try {
                new AuthenticationMechanism($value);
                self::fail('Invalid mechanism accepted.');
            } catch (InvalidAuthenticationValueException $failure) {
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, $failure->getMessage());
                    self::assertStringNotContainsString($value, (string) $failure);
                }
            }
        }
    }

    public function testInstantUsesOnlyExplicitOrderedIntegerSeconds(): void
    {
        $earlier = new AuthenticationInstant(100);
        $same = new AuthenticationInstant(100);
        $later = new AuthenticationInstant(120);

        self::assertSame(100, $earlier->seconds());
        self::assertFalse($same->isAfter($earlier));
        self::assertTrue($later->isAfter($earlier));
        self::assertSame(0, $same->elapsedSince($earlier));
        self::assertSame(20, $later->elapsedSince($earlier));

        $this->expectException(InvalidAuthenticationValueException::class);
        $earlier->elapsedSince($later);
    }

    public function testInstantRejectsNegativeSeconds(): void
    {
        $this->expectException(InvalidAuthenticationValueException::class);
        new AuthenticationInstant(-1);
    }

    public function testStrengthBoundsAndComparisonAreExact(): void
    {
        self::assertSame(1, (new AuthenticationStrength(1))->level());
        self::assertSame(255, (new AuthenticationStrength(255))->level());
        self::assertTrue((new AuthenticationStrength(2))->satisfies(new AuthenticationStrength(2)));
        self::assertTrue((new AuthenticationStrength(3))->satisfies(new AuthenticationStrength(2)));
        self::assertFalse((new AuthenticationStrength(1))->satisfies(new AuthenticationStrength(2)));

        foreach ([0, 256] as $level) {
            try {
                new AuthenticationStrength($level);
                self::fail('Invalid strength accepted.');
            } catch (InvalidAuthenticationValueException $failure) {
                self::assertSame('KGN-AUTHENTICATION-1006', $failure->diagnosticId());
            }
        }
    }

    public function testRequirementBoundsAndIdentityAccessorsAreExact(): void
    {
        $strength = new AuthenticationStrength(4);
        $zero = new AuthenticationRequirement($strength, 0);
        $maximum = new AuthenticationRequirement($strength, 31_536_000);

        self::assertSame($strength, $zero->minimumStrength());
        self::assertSame(0, $zero->maximumAgeSeconds());
        self::assertSame(31_536_000, $maximum->maximumAgeSeconds());

        foreach ([-1, 31_536_001] as $age) {
            try {
                new AuthenticationRequirement($strength, $age);
                self::fail('Invalid maximum age accepted.');
            } catch (InvalidAuthenticationValueException $failure) {
                self::assertSame('KGN-AUTHENTICATION-1005', $failure->diagnosticId());
            }
        }
    }
}
