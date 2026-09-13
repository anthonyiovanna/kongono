<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Security;

use Kongono\ViewHttp\Exception\InvalidViewHttpPolicyException;
use Kongono\ViewHttp\Policy\ViewResponseHeader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewHttpDisclosureTest extends TestCase
{
    #[DataProvider('privateInputs')]
    public function testRejectedPrivateHeaderMaterialIsNeverDisclosed(string $value): void
    {
        try {
            new ViewResponseHeader('X-Test', [$value]);
            self::fail('Expected invalid header value.');
        } catch (InvalidViewHttpPolicyException $exception) {
            self::assertStringNotContainsString($value, $exception->getMessage());
            self::assertStringNotContainsString($value, (string) $exception);
            self::assertStringNotContainsString($value, $exception->diagnosticTitle());
            self::assertStringNotContainsString($value, $exception->diagnosticRemediation());
        }
    }

    /** @return iterable<int, array{string}> */
    public static function privateInputs(): iterable
    {
        yield ["Bearer secret-token\r"];
        yield ["session=private\n"];
        yield ["credential\0value"];
        yield [str_repeat('private', 200)];
        yield ['nonascii-π'];
    }

    public function testDiagnosticContextContainsOnlySafeAggregateEvidence(): void
    {
        try {
            new ViewResponseHeader('X-Test', [str_repeat('s', 1025)]);
            self::fail('Expected invalid header value.');
        } catch (InvalidViewHttpPolicyException $exception) {
            $values = [];
            foreach ($exception->diagnosticContext()->fields() as $field) {
                $values[$field->key()] = $field->value();
            }
            self::assertSame('header_value_length', $values['violation']);
            self::assertSame(1024, $values['limit']);
            self::assertSame(1025, $values['observed_count']);
            self::assertCount(3, $values);
        }
    }

    public function testReservedHeaderNameIsNotEchoed(): void
    {
        try {
            new ViewResponseHeader('Authorization', ["private\n"]);
            self::fail('Expected invalid header value.');
        } catch (InvalidViewHttpPolicyException $exception) {
            self::assertStringNotContainsString('Authorization', (string) $exception);
            self::assertStringNotContainsString('private', (string) $exception);
        }
    }
}
