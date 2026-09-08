<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Address;

use Kongono\NetworkEgressRuntime\Address\ResolutionEvidence;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use PHPUnit\Framework\TestCase;

final class ResolutionEvidenceTest extends TestCase
{
    public function testOrderAndSelectedMemberAreRetainedInDetachedLists(): void
    {
        $first = new ResolvedAddress('8.8.8.8');
        $second = new ResolvedAddress('1.1.1.1');
        $evidence = new ResolutionEvidence([$first, $second], $second);
        $copy = $evidence->addresses();
        array_shift($copy);
        self::assertSame(['8.8.8.8', '1.1.1.1'], array_map(static fn(ResolvedAddress $address): string => $address->value(), $evidence->addresses()));
        self::assertSame($second, $evidence->selectedAddress());
    }

    public function testEmptyDuplicateWrongTypeAndMissingSelectedAreRejected(): void
    {
        $address = new ResolvedAddress('8.8.8.8');
        foreach ([[/* empty */], [$address, $address], [$address, 'invalid'], [$address]] as $index => $addresses) {
            try {
                new ResolutionEvidence($addresses, 3 === $index ? new ResolvedAddress('1.1.1.1') : $address);
                self::fail('Expected invalid resolution evidence.');
            } catch (InvalidEgressRuntimeException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testMaximumAndMaximumPlusOneAreDistinguished(): void
    {
        $addresses = [];
        for ($last = 1; $last <= 64; ++$last) {
            $addresses[] = new ResolvedAddress('11.0.0.' . $last);
        }
        self::assertCount(64, (new ResolutionEvidence($addresses, $addresses[63]))->addresses());
        $addresses[] = new ResolvedAddress('11.0.0.65');
        $this->expectException(InvalidEgressRuntimeException::class);
        new ResolutionEvidence($addresses, $addresses[64]);
    }
}
