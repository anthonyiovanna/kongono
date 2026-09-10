<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Internal;

use Kongono\ExternalServiceResolverSystem\Internal\AddressInfoLookupFailure;
use Kongono\ExternalServiceResolverSystem\Internal\NativeAddressInfoLookup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeAddressInfoLookupTest extends TestCase
{
    #[DataProvider('numericLoopbacks')]
    public function testNumericLoopbacksUseTheNativeBindingWithoutDns(string $host): void
    {
        $result = (new NativeAddressInfoLookup())->lookup($host, '443', [
            'ai_socktype' => SOCK_STREAM,
            'ai_protocol' => SOL_TCP,
            'ai_flags' => AI_NUMERICSERV | AI_NUMERICHOST,
        ], 64);
        self::assertNull($result->failure());
        self::assertNotEmpty($result->records());
    }

    /** @return iterable<array{string}> */
    public static function numericLoopbacks(): iterable
    {
        yield ['127.0.0.1'];
        yield ['::1'];
    }

    public function testLimitIsAppliedBeforeAnyResultCanBeReturned(): void
    {
        $result = (new NativeAddressInfoLookup())->lookup('127.0.0.1', '443', [
            'ai_socktype' => SOCK_STREAM,
            'ai_protocol' => SOL_TCP,
            'ai_flags' => AI_NUMERICSERV | AI_NUMERICHOST,
        ], 0);
        self::assertSame(AddressInfoLookupFailure::CandidateLimit, $result->failure());
    }
}
