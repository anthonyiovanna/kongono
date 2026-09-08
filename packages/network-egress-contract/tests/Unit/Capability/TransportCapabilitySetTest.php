<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Capability;

use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class TransportCapabilitySetTest extends TestCase
{
    public function testCanonicalSetAccessAndMaximumAreExact(): void
    {
        self::assertSame(['http_1_1', 'http_2', 'http_3', 'tls', 'request_streaming', 'response_streaming', 'cancellation', 'connection_reuse', 'proxy', 'mutual_tls', 'trailers'], array_column(TransportCapability::cases(), 'value'));
        $set = new TransportCapabilitySet(TransportCapability::cases());
        $copy = $set->all();
        array_pop($copy);
        self::assertCount(11, $set->all());
        self::assertSame(11, $set->count());
        self::assertTrue($set->contains(TransportCapability::Tls));
        self::assertSame(['__construct', 'all', 'contains', 'count'], array_map(static fn(ReflectionMethod $method): string => $method->getName(), (new ReflectionClass($set))->getMethods(ReflectionMethod::IS_PUBLIC)));
    }

    public function testNonlistEmptyInvalidDuplicateAndOrderFailClosed(): void
    {
        foreach ([[1 => TransportCapability::Http11], [], [TransportCapability::Http11, TransportCapability::Http11], [TransportCapability::Tls, TransportCapability::Http11]] as $values) {
            try {
                (new ReflectionClass(TransportCapabilitySet::class))->newInstanceArgs([$values]);
                self::fail('Expected capability rejection.');
            } catch (InvalidNetworkEgressContractException $exception) {
                self::assertSame('KGN-EGRESS-CONTRACT-1505', $exception->diagnosticId());
            }
        }
    }
}
