<?php

declare(strict_types=1);

namespace Kongono\Http\Message\Tests\Unit\Internal;

use Kongono\Http\Message\Exception\ExceptionInterface;
use Kongono\Http\Message\Exception\InvalidMessageArgumentException;
use Kongono\Http\Message\Internal\HeaderCollection;
use PHPUnit\Framework\TestCase;

final class HeaderCollectionTest extends TestCase
{
    public function testCaseVariantsMergeAndMutationsPreserveRequiredSpelling(): void
    {
        $headers = new HeaderCollection(['X-Test' => 'one', 'x-test' => ['two', '']]);
        self::assertSame(['X-Test' => ['one', 'two', '']], $headers->all());
        self::assertSame('one,two,', $headers->line('X-TEST'));
        self::assertSame(['new'], $headers->with('X-TEST', 'new')->get('x-test'));
        self::assertSame(['one', 'two', '', 'three'], $headers->withAdded('x-TEST', 'three')->get('X-Test'));
        self::assertSame([], $headers->without('x-test')->all());
        self::assertFalse($headers->has("bad\nname"));
    }

    public function testTokenBoundariesAndUnsafeValuesAreRejectedWithoutDisclosure(): void
    {
        $boundary = "!#$%&'*+-.^_`|~09AZaz";
        self::assertSame([$boundary => ['value']], (new HeaderCollection([$boundary => 'value']))->all());
        foreach (['', 'bad name', 'x:y', 'x/y', 'x(y)', "x\0y", "x\x7Fy"] as $name) {
            try {
                new HeaderCollection([$name => 'secret']);
                self::fail('Invalid name must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                if ('' !== $name) {
                    self::assertStringNotContainsString($name, $exception->getMessage());
                } else {
                    self::assertSame('Invalid HTTP header name', $exception->getMessage());
                }
            }
        }
        foreach (["a\r", "a\n", "a\0", "a\x01", "a\x7F"] as $value) {
            $this->expectUnsafeValue($value);
        }
        self::assertSame(["a\tb", "a\x80b"], (new HeaderCollection(['X' => ["a\tb", "a\x80b"]]))->get('x'));
    }

    public function testInvalidListShapeAndEntryAreDeterministic(): void
    {
        try {
            new HeaderCollection(['X' => [1 => 'x']]);
            self::fail('A non-list value must fail.');
        } catch (InvalidMessageArgumentException $exception) {
            self::assertSame('KGN-HTTP-1003', $exception->diagnosticId());
            self::assertSame([1, 'int'], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
        }

        try {
            new HeaderCollection(['X' => ['ok', 3, null]]);
            self::fail('The first non-string entry must fail.');
        } catch (InvalidMessageArgumentException $exception) {
            self::assertSame('KGN-HTTP-1004', $exception->diagnosticId());
            self::assertSame([1, 'int'], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
        }
    }

    public function testLookupAbsenceAndMutationSpellingRulesAreExact(): void
    {
        $headers = new HeaderCollection(['First-Case' => ['one', 'two']]);
        self::assertTrue($headers->has('first-case'));
        self::assertSame(['one', 'two'], $headers->get('FIRST-CASE'));
        self::assertSame('one,two', $headers->line('first-case'));
        self::assertFalse($headers->has('bad name'));
        self::assertSame([], $headers->get('bad name'));
        self::assertSame('', $headers->line('absent'));

        self::assertSame(['REPLACED' => ['new']], $headers->with('REPLACED', 'new')->without('first-case')->all());
        self::assertSame(['First-Case' => ['one', 'two', 'three']], $headers->withAdded('first-CASE', 'three')->all());
        self::assertSame(['New-Name' => ['']], (new HeaderCollection())->withAdded('New-Name', '')->all());
    }

    public function testUnsafeValueVocabularyUsesDeterministicFirstViolation(): void
    {
        $cases = ["\0\r" => 'nul', "\r\n" => 'carriage_return', "\n\x01" => 'line_feed', "\x01\x7F" => 'control', "\x7F\0" => 'del'];
        foreach ($cases as $value => $violation) {
            try {
                new HeaderCollection(['X' => ['safe', $value]]);
                self::fail('Unsafe bytes must fail.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1005', $exception->diagnosticId());
                self::assertSame([1, $violation], array_map(static fn($field): string|int|bool => $field->value(), $exception->diagnosticContext()->fields()));
                $this->assertRawAbsentFromPackageSurfaces($value, $exception);
            }
        }
    }

    public function testEveryProhibitedHeaderByteHasTheExactBoundedFailure(): void
    {
        $prohibited = [...range(0x00, 0x08), ...range(0x0A, 0x1F), 0x7F];
        self::assertCount(32, $prohibited);

        foreach ($prohibited as $byte) {
            $value = 'private-prefix' . chr($byte) . 'private-suffix';
            $violation = match ($byte) {
                0x00 => 'nul',
                0x0D => 'carriage_return',
                0x0A => 'line_feed',
                0x7F => 'del',
                default => 'control',
            };

            try {
                new HeaderCollection(['X-Proof' => ['safe', $value]]);
                self::fail(sprintf('Header byte 0x%02X must be rejected.', $byte));
            } catch (InvalidMessageArgumentException $exception) {
                self::assertSame('KGN-HTTP-1005', $exception->diagnosticId());
                self::assertSame([1, $violation], array_map(
                    static fn($field): string|int|bool => $field->value(),
                    $exception->diagnosticContext()->fields(),
                ));
                $this->assertRawAbsentFromPackageSurfaces($value, $exception);
            }
        }

        self::assertSame(["a\tb", "a\x80b"], (new HeaderCollection(['X-Proof' => ["a\tb", "a\x80b"]]))->get('x-proof'));
    }

    public function testIntegerAndPhpCoercedHeaderKeysUseTheBoundedPackageFailure(): void
    {
        foreach ([[0 => 'value'], ['0' => 'value']] as $headers) {
            try {
                new HeaderCollection($headers);
                self::fail('Every non-string header key must fail through the package boundary.');
            } catch (InvalidMessageArgumentException $exception) {
                self::assertInstanceOf(ExceptionInterface::class, $exception);
                self::assertSame('KGN-HTTP-1002', $exception->diagnosticId());
                self::assertSame(1, $exception->diagnosticContext()->fields()[0]->value());
                $this->assertRawAbsentFromPackageSurfaces('value', $exception);
            }
        }
    }

    private function expectUnsafeValue(string $value): void
    {
        try {
            new HeaderCollection(['X' => $value]);
            self::fail('Unsafe value must fail.');
        } catch (InvalidMessageArgumentException $exception) {
            self::assertStringNotContainsString($value, $exception->getMessage());
        }
    }

    private function assertRawAbsentFromPackageSurfaces(string $raw, ExceptionInterface $exception): void
    {
        $surfaces = [
            $exception->getMessage(),
            $exception->diagnosticId(),
            $exception->diagnosticTitle(),
            $exception->diagnosticRemediation(),
            $exception->diagnosticContext()->operation(),
            $exception->diagnosticContext()->violatedInvariant(),
            ...array_map(
                static fn($field): string => $field->key() . '|' . $field->label() . '|' . (string) $field->value(),
                $exception->diagnosticContext()->fields(),
            ),
        ];
        foreach ($surfaces as $surface) {
            self::assertStringNotContainsString($raw, $surface);
        }
    }
}
