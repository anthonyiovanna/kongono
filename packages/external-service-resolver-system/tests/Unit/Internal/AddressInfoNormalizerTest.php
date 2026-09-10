<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Internal;

use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoNormalizer;
use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoRecord;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\NetworkEgressRuntime\Address\AddressCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AddressInfoNormalizerTest extends TestCase
{
    public function testDuplicatesCollapseAndBothPreferencesSelectDeterministically(): void
    {
        $records = [self::v6('2001:db8::2'), self::v4('10.0.0.2'), self::v4('10.0.0.1'), self::v6('2001:db8::1'), self::v4('10.0.0.1')];
        $ipv4 = AddressInfoNormalizer::normalize($records, 443, AddressFamilyPreference::Ipv4First);
        self::assertSame(['10.0.0.1', '10.0.0.2', '2001:db8::1', '2001:db8::2'], array_map(static fn($address): string => $address->value(), $ipv4->evidence()?->addresses() ?? []));
        self::assertSame('10.0.0.1', $ipv4->evidence()?->selectedAddress()->value());
        $ipv6 = AddressInfoNormalizer::normalize(array_reverse($records), 443, AddressFamilyPreference::Ipv6First);
        self::assertSame(['2001:db8::1', '2001:db8::2', '10.0.0.1', '10.0.0.2'], array_map(static fn($address): string => $address->value(), $ipv6->evidence()?->addresses() ?? []));
        self::assertSame('2001:db8::1', $ipv6->evidence()?->selectedAddress()->value());
    }

    #[DataProvider('invalidRecords')]
    public function testAnyMalformedRecordRejectsTheCompleteResult(AddressInfoRecord $record): void
    {
        $result = AddressInfoNormalizer::normalize([self::v4('8.8.8.8'), $record], 443, AddressFamilyPreference::Ipv4First);
        self::assertFalse($result->succeeded());
        self::assertSame('system_resolver_result_invalid', $result->failure()?->reason());
    }

    /** @return iterable<array{AddressInfoRecord}> */
    public static function invalidRecords(): iterable
    {
        yield [new AddressInfoRecord([])];
        yield [new AddressInfoRecord(['ai_family' => 999, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => []])];
        yield [new AddressInfoRecord(['ai_family' => AF_INET, 'ai_socktype' => SOCK_DGRAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin_addr' => '8.8.8.8', 'sin_port' => 443]])];
        yield [new AddressInfoRecord(['ai_family' => AF_INET, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => 999, 'ai_addr' => ['sin_addr' => '8.8.8.8', 'sin_port' => 443]])];
        yield [new AddressInfoRecord(['ai_family' => AF_INET, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin_addr' => '8.8.8.8', 'sin_port' => 80]])];
        yield [new AddressInfoRecord(['ai_family' => AF_INET6, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin6_addr' => '8.8.8.8', 'sin6_port' => 443]])];
        yield [new AddressInfoRecord(['ai_family' => AF_INET6, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin6_addr' => 'fe80::1%1', 'sin6_port' => 443]])];
    }

    public function testConservativeAddressCategoriesAreRetainedWithoutFiltering(): void
    {
        $expected = [
            '8.8.8.8' => AddressCategory::Public,
            '10.0.0.1' => AddressCategory::Private,
            '127.0.0.1' => AddressCategory::Loopback,
            '169.254.1.1' => AddressCategory::LinkLocal,
            '224.0.0.1' => AddressCategory::Multicast,
            '0.0.0.0' => AddressCategory::Unspecified,
            '192.0.2.1' => AddressCategory::Documentation,
            '100.64.0.1' => AddressCategory::Reserved,
        ];
        $result = AddressInfoNormalizer::normalize(array_map(self::v4(...), array_keys($expected)), 443, AddressFamilyPreference::Ipv4First);
        self::assertTrue($result->succeeded());
        $actual = [];
        foreach ($result->evidence()?->addresses() ?? [] as $address) {
            $actual[$address->value()] = $address->category();
        }
        self::assertCount(count($expected), $actual);
        foreach ($expected as $value => $category) {
            self::assertSame($category, $actual[$value] ?? null, $value);
        }
    }

    private static function v4(string $value): AddressInfoRecord
    {
        return new AddressInfoRecord(['ai_family' => AF_INET, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin_addr' => $value, 'sin_port' => 443]]);
    }

    private static function v6(string $value): AddressInfoRecord
    {
        return new AddressInfoRecord(['ai_family' => AF_INET6, 'ai_socktype' => SOCK_STREAM, 'ai_protocol' => SOL_TCP, 'ai_addr' => ['sin6_addr' => $value, 'sin6_port' => 443]]);
    }
}
