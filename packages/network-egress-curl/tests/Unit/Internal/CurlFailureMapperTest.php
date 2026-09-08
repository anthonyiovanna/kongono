<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressCurl\Internal\CurlFailureMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurlFailureMapperTest extends TestCase
{
    /** @return iterable<string, array{int, ?string, int, EgressFailureKind}> */
    public static function mappings(): iterable
    {
        yield 'timeout' => [28, null, 0, EgressFailureKind::Timeout];
        yield 'tls' => [60, null, 0, EgressFailureKind::Tls];
        yield 'connection' => [7, null, 0, EgressFailureKind::Connection];
        yield 'protocol' => [92, null, 0, EgressFailureKind::Protocol];
        yield 'budget' => [23, 'response_budget', 10, EgressFailureKind::Budget];
        yield 'uncertain' => [99, null, 10, EgressFailureKind::UncertainEffect];
    }

    #[DataProvider('mappings')]
    public function testNumericMappingDoesNotUseForeignText(int $code, ?string $violation, int $requestBytes, EgressFailureKind $expected): void
    {
        $failure = (new CurlFailureMapper())->map($code, $violation, $requestBytes);
        self::assertSame($expected, $failure->kind());
        self::assertMatchesRegularExpression('/\A[a-z0-9_]+\z/D', $failure->reason());
    }
}
