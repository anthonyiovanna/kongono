<?php

declare(strict_types=1);

namespace Kongono\Authentication\Tests\Unit;

use Kongono\Authentication\AuthenticationEvidence;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationRequest;
use Kongono\Authentication\AuthenticationRequirement;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\Exception\InvalidAuthenticationValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class AuthenticationRequestTest extends TestCase
{
    public function testRequestAcceptsZeroOneAndEightEntriesAndPreservesExplicitInputs(): void
    {
        $now = new AuthenticationInstant(500);
        $requirement = new AuthenticationRequirement(new AuthenticationStrength(2), 30);
        $zero = new AuthenticationRequest([], $now, $requirement);
        self::assertSame([], $zero->evidence());
        self::assertSame($now, $zero->now());
        self::assertSame($requirement, $zero->requirement());

        $one = new SyntheticRequestEvidence('synthetic.one');
        self::assertSame([$one], (new AuthenticationRequest([$one], $now, $requirement))->evidence());

        $eight = [];
        for ($index = 0; $index < 8; ++$index) {
            $eight[] = new SyntheticRequestEvidence('synthetic.' . $index);
        }
        self::assertSame($eight, (new AuthenticationRequest($eight, $now, $requirement))->evidence());
    }

    public function testRequestDetachesReindexesAndPreservesEvidenceOrder(): void
    {
        $first = new SyntheticRequestEvidence('synthetic.first');
        $second = new SyntheticRequestEvidence('synthetic.second');
        $input = [4 => $first, 9 => $second];
        $request = new AuthenticationRequest(
            $input,
            new AuthenticationInstant(5),
            new AuthenticationRequirement(new AuthenticationStrength(1), 5),
        );
        $input[4] = new SyntheticRequestEvidence('synthetic.changed');

        self::assertSame([$first, $second], $request->evidence());
        self::assertSame([0, 1], array_keys($request->evidence()));
    }

    public function testRequestRejectsMoreThanEightEvidenceEntries(): void
    {
        $entries = [];
        for ($index = 0; $index < 9; ++$index) {
            $entries[] = new SyntheticRequestEvidence('synthetic.' . $index);
        }

        $this->expectException(InvalidAuthenticationValueException::class);
        new AuthenticationRequest(
            $entries,
            new AuthenticationInstant(5),
            new AuthenticationRequirement(new AuthenticationStrength(1), 5),
        );
    }

    public function testRequestRejectsInvalidMembersAtRuntime(): void
    {
        $reflection = new ReflectionClass(AuthenticationRequest::class);

        $this->expectException(InvalidAuthenticationValueException::class);
        $reflection->newInstance(
            [new stdClass()],
            new AuthenticationInstant(5),
            new AuthenticationRequirement(new AuthenticationStrength(1), 5),
        );
    }
}

final readonly class SyntheticRequestEvidence implements AuthenticationEvidence
{
    private AuthenticationMechanism $mechanism;

    public function __construct(string $mechanism)
    {
        $this->mechanism = new AuthenticationMechanism($mechanism);
    }

    public function mechanism(): AuthenticationMechanism
    {
        return $this->mechanism;
    }
}
