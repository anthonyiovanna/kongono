<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Kongono\NetworkEgressCurl\Internal\CurlRequestGuard;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\TestCase;

final class CurlRequestGuardTest extends TestCase
{
    public function testValidRequestIsAdmittedWithoutConsumingBody(): void
    {
        $body = FakeCurlExecutor::stream('payload', 2);
        $request = FakeCurlExecutor::request(body: $body, headers: ['Content-Length' => '5']);
        $reader = CurlRequestGuard::admit($request, FakeCurlExecutor::profile());
        self::assertSame(2, $body->tell());
        self::assertSame(5, $reader->remainingBytes());
    }

    public function testSecureProfileRejectsPlainHttp(): void
    {
        $this->expectException(InvalidCurlTransportProfileException::class);
        CurlRequestGuard::admit(FakeCurlExecutor::request('http://example.test/resource'), FakeCurlExecutor::profile());
    }

    public function testTransportOwnedHeaderIsRejected(): void
    {
        $this->expectException(InvalidCurlTransportProfileException::class);
        CurlRequestGuard::admit(FakeCurlExecutor::request(headers: ['Connection' => 'close']), FakeCurlExecutor::profile());
    }

    public function testMismatchedContentLengthIsRejected(): void
    {
        $this->expectException(InvalidCurlTransportProfileException::class);
        CurlRequestGuard::admit(FakeCurlExecutor::request(body: FakeCurlExecutor::stream('abc'), headers: ['Content-Length' => '2']), FakeCurlExecutor::profile());
    }
}
