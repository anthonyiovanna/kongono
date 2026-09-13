<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Unit\Policy;

use Kongono\ViewHttp\Exception\InvalidViewHttpPolicyException;
use Kongono\ViewHttp\Policy\ViewContentType;
use Kongono\ViewHttp\Policy\ViewResponseHeader;
use Kongono\ViewHttp\Policy\ViewResponsePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewResponsePolicyTest extends TestCase
{
    public function testPolicyPreservesAllExplicitChoices(): void
    {
        $headers = [new ViewResponseHeader('X-One', ['a']), new ViewResponseHeader('X-Two', ['b', 'c'])];
        $policy = new ViewResponsePolicy(202, 'Accepted', ViewContentType::HtmlUtf8, $headers);
        self::assertSame(202, $policy->statusCode());
        self::assertSame('Accepted', $policy->reasonPhrase());
        self::assertSame(ViewContentType::HtmlUtf8, $policy->contentType());
        self::assertSame($headers, $policy->headers());
    }

    #[DataProvider('invalidStatusAndReason')]
    public function testInvalidStatusOrReasonFailsClosed(int $status, string $reason): void
    {
        $this->expectException(InvalidViewHttpPolicyException::class);
        new ViewResponsePolicy($status, $reason, ViewContentType::HtmlUtf8);
    }

    /** @return iterable<int, array{int, string}> */
    public static function invalidStatusAndReason(): iterable
    {
        yield [199, ''];
        yield [600, ''];
        yield [200, str_repeat('x', 129)];
        yield [200, "bad\rreason"];
        yield [200, "bad\nreason"];
        yield [200, "bad\0reason"];
        yield [200, 'café'];
    }

    public function testCaseInsensitiveDuplicateHeaderNamesAreRejected(): void
    {
        $this->expectException(InvalidViewHttpPolicyException::class);
        new ViewResponsePolicy(200, '', ViewContentType::HtmlUtf8, [new ViewResponseHeader('X-Test', ['a']), new ViewResponseHeader('x-test', ['b'])]);
    }

    public function testMoreThanThirtyTwoHeadersAreRejected(): void
    {
        $headers = [];
        for ($index = 0; $index < 33; ++$index) {
            $headers[] = new ViewResponseHeader('X-H' . $index, ['v']);
        }
        $this->expectException(InvalidViewHttpPolicyException::class);
        new ViewResponsePolicy(200, '', ViewContentType::HtmlUtf8, $headers);
    }

    public function testAggregateHeaderBytesAreBounded(): void
    {
        $headers = [];
        for ($index = 0; $index < 17; ++$index) {
            $headers[] = new ViewResponseHeader('X-H' . $index, [str_repeat('v', 1024)]);
        }
        $this->expectException(InvalidViewHttpPolicyException::class);
        new ViewResponsePolicy(200, '', ViewContentType::HtmlUtf8, $headers);
    }

    public function testPolicyBoundariesAreAccepted(): void
    {
        $headers = [];
        for ($index = 0; $index < 32; ++$index) {
            $headers[] = new ViewResponseHeader('X-H' . $index, ['v']);
        }
        $policy = new ViewResponsePolicy(599, str_repeat('R', 128), ViewContentType::HtmlUtf8, $headers);
        self::assertSame(599, $policy->statusCode());
        self::assertSame(128, strlen($policy->reasonPhrase()));
        self::assertCount(32, $policy->headers());
        foreach ($policy->headers() as $index => $header) {
            self::assertSame('X-H' . $index, $header->name());
        }
    }

    public function testPolicyAcceptsValuesImmediatelyBelowEveryMaximum(): void
    {
        $headers = [];
        for ($index = 0; $index < 31; ++$index) {
            $headers[] = new ViewResponseHeader('X-H' . $index, ['v']);
        }
        $policy = new ViewResponsePolicy(598, str_repeat('R', 127), ViewContentType::HtmlUtf8, $headers);
        self::assertSame(598, $policy->statusCode());
        self::assertSame(127, strlen($policy->reasonPhrase()));
        self::assertCount(31, $policy->headers());
    }

    #[DataProvider('aggregateHeaderByteBoundaries')]
    public function testAggregateHeaderByteMaximumIsExact(int $finalValueBytes, bool $accepted): void
    {
        $headers = [
            new ViewResponseHeader(str_repeat('A', 64), array_fill(0, 8, str_repeat('x', 1024))),
            new ViewResponseHeader(str_repeat('B', 64), [...array_fill(0, 7, str_repeat('y', 1024)), str_repeat('z', $finalValueBytes)]),
        ];
        if (!$accepted) {
            $this->expectException(InvalidViewHttpPolicyException::class);
        }
        $policy = new ViewResponsePolicy(200, '', ViewContentType::HtmlUtf8, $headers);
        self::assertCount(2, $policy->headers());
    }

    /** @return iterable<string, array{int, bool}> */
    public static function aggregateHeaderByteBoundaries(): iterable
    {
        yield 'below' => [895, true];
        yield 'at' => [896, true];
        yield 'above' => [897, false];
    }
}
