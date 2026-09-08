<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Tests\Unit\Address;

use Kongono\NetworkEgressRuntime\Address\AddressCategory;
use Kongono\NetworkEgressRuntime\Address\AddressFamily;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResolvedAddressTest extends TestCase
{
    #[DataProvider('classifiedAddresses')]
    public function testCanonicalAddressesAreConservativelyClassified(string $value, AddressFamily $family, AddressCategory $category): void
    {
        $address = new ResolvedAddress($value);
        self::assertSame($value, $address->value());
        self::assertSame($family, $address->family());
        self::assertSame($category, $address->category());
        self::assertTrue($address->equals(new ResolvedAddress($value)));
    }

    /** @return iterable<string, array{string, AddressFamily, AddressCategory}> */
    public static function classifiedAddresses(): iterable
    {
        yield 'public ipv4' => ['8.8.8.8', AddressFamily::Ipv4, AddressCategory::Public];
        yield 'private ipv4' => ['10.0.0.0', AddressFamily::Ipv4, AddressCategory::Private];
        yield 'private ipv4 boundary' => ['172.31.255.255', AddressFamily::Ipv4, AddressCategory::Private];
        yield 'loopback ipv4' => ['127.255.255.255', AddressFamily::Ipv4, AddressCategory::Loopback];
        yield 'link local ipv4' => ['169.254.0.0', AddressFamily::Ipv4, AddressCategory::LinkLocal];
        yield 'multicast ipv4' => ['239.255.255.255', AddressFamily::Ipv4, AddressCategory::Multicast];
        yield 'unspecified ipv4' => ['0.0.0.0', AddressFamily::Ipv4, AddressCategory::Unspecified];
        yield 'documentation ipv4' => ['203.0.113.255', AddressFamily::Ipv4, AddressCategory::Documentation];
        yield 'reserved ipv4' => ['100.64.0.0', AddressFamily::Ipv4, AddressCategory::Reserved];
        yield 'public ipv6' => ['2606:4700:4700::1111', AddressFamily::Ipv6, AddressCategory::Public];
        yield 'private ipv6' => ['fdff::1', AddressFamily::Ipv6, AddressCategory::Private];
        yield 'loopback ipv6' => ['::1', AddressFamily::Ipv6, AddressCategory::Loopback];
        yield 'link local ipv6' => ['fe80::1', AddressFamily::Ipv6, AddressCategory::LinkLocal];
        yield 'multicast ipv6' => ['ff00::', AddressFamily::Ipv6, AddressCategory::Multicast];
        yield 'unspecified ipv6' => ['::', AddressFamily::Ipv6, AddressCategory::Unspecified];
        yield 'documentation ipv6' => ['2001:db8::1', AddressFamily::Ipv6, AddressCategory::Documentation];
        yield 'nat64 reserved' => ['64:ff9b::1', AddressFamily::Ipv6, AddressCategory::Reserved];
        yield 'teredo reserved' => ['2001::1', AddressFamily::Ipv6, AddressCategory::Reserved];
        yield 'six to four reserved' => ['2002::1', AddressFamily::Ipv6, AddressCategory::Reserved];
    }

    #[DataProvider('invalidAddresses')]
    public function testAmbiguousAndNoncanonicalAddressesAreRejected(string $value): void
    {
        $this->expectException(InvalidEgressRuntimeException::class);
        new ResolvedAddress($value);
    }

    /** @return iterable<array{string}> */
    public static function invalidAddresses(): iterable
    {
        foreach (['example.com', ' 8.8.8.8', '010.0.0.1', '[::1]', 'fe80::1%1', '2001:0db8::1', '::ffff:192.0.2.1'] as $value) {
            yield [$value];
        }
    }
}
