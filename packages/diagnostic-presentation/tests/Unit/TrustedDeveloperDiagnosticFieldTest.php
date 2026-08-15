<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class TrustedDeveloperDiagnosticFieldTest extends TestCase
{
    public function testAllScalarKindsDisclosuresAndTruncationStatesArePreserved(): void
    {
        $text = new TrustedDeveloperDiagnosticField('text', 'Text', DiagnosticValueKind::Text, 'value', DiagnosticDisclosure::Protected, false);
        $truncated = new TrustedDeveloperDiagnosticField('truncated', 'Truncated', DiagnosticValueKind::Text, 'value...[truncated]', DiagnosticDisclosure::Safe, true);
        $integer = new TrustedDeveloperDiagnosticField('count', 'Count', DiagnosticValueKind::Integer, PHP_INT_MIN, DiagnosticDisclosure::Safe, null);
        $boolean = new TrustedDeveloperDiagnosticField('enabled', 'Enabled', DiagnosticValueKind::Boolean, false, DiagnosticDisclosure::Protected, null);

        self::assertSame(['text', 'Text', DiagnosticValueKind::Text, 'value', DiagnosticDisclosure::Protected, false], [$text->key(), $text->label(), $text->kind(), $text->value(), $text->disclosure(), $text->textWasTruncated()]);
        self::assertSame('value...[truncated]', $truncated->value());
        self::assertTrue($truncated->textWasTruncated());
        self::assertSame(PHP_INT_MIN, $integer->value());
        self::assertNull($integer->textWasTruncated());
        self::assertFalse($boolean->value());
        self::assertNull($boolean->textWasTruncated());
    }

    public function testKeyAndLabelBoundsAreExact(): void
    {
        self::assertSame(str_repeat('a', 64), $this->field(str_repeat('a', 64))->key());
        self::assertSame(str_repeat("\xC3\xA9", 48), $this->field('key', str_repeat("\xC3\xA9", 48))->label());

        foreach (['', 'Upper', '_leading', 'trailing_', 'double__underscore', '2leading', "non\xC3\xAFascii", str_repeat('a', 65)] as $key) {
            $this->assertFailure(
                fn(): TrustedDeveloperDiagnosticField => $this->field($key),
                DiagnosticPresentationDiagnostic::InvalidDiagnosticFieldKey,
            );
        }
        foreach (['', "\xFF", str_repeat('a', 97)] as $label) {
            $this->assertFailure(
                fn(): TrustedDeveloperDiagnosticField => $this->field('key', $label),
                DiagnosticPresentationDiagnostic::InvalidDiagnosticFieldLabel,
            );
        }
    }

    public function testExactKindMismatchesAreRejected(): void
    {
        foreach ([
            [DiagnosticValueKind::Text, 1, false],
            [DiagnosticValueKind::Text, true, false],
            [DiagnosticValueKind::Integer, '1', null],
            [DiagnosticValueKind::Integer, false, null],
            [DiagnosticValueKind::Boolean, 'true', null],
            [DiagnosticValueKind::Boolean, 1, null],
        ] as [$kind, $value, $truncated]) {
            $this->assertFailure(
                static fn(): TrustedDeveloperDiagnosticField => new TrustedDeveloperDiagnosticField('field', 'Field', $kind, $value, DiagnosticDisclosure::Safe, $truncated),
                DiagnosticPresentationDiagnostic::DiagnosticFieldValueKindMismatch,
            );
        }
    }

    public function testPrintableAsciiAndTextBoundsAreExactWithoutMutation(): void
    {
        $allPrintable = '';
        for ($byte = 0x20; $byte <= 0x7E; ++$byte) {
            $allPrintable .= chr($byte);
        }
        $field = new TrustedDeveloperDiagnosticField('field', 'Field', DiagnosticValueKind::Text, $allPrintable, DiagnosticDisclosure::Safe, false);

        self::assertSame($allPrintable, $field->value());
        self::assertSame(str_repeat('a', 512), $this->text(str_repeat('a', 512), false)->value());
        foreach (["line\n", "tab\t", "nul\0", "\x7F", "\xFF", str_repeat('a', 513)] as $value) {
            $this->assertFailure(
                fn(): TrustedDeveloperDiagnosticField => $this->text($value, false),
                DiagnosticPresentationDiagnostic::InvalidDiagnosticTextRepresentation,
            );
        }
    }

    public function testTruncationStateRulesAreExact(): void
    {
        foreach ([
            [DiagnosticValueKind::Text, 'value', null],
            [DiagnosticValueKind::Integer, 1, false],
            [DiagnosticValueKind::Boolean, true, true],
            [DiagnosticValueKind::Text, 'missing suffix', true],
        ] as [$kind, $value, $truncated]) {
            $this->assertFailure(
                static fn(): TrustedDeveloperDiagnosticField => new TrustedDeveloperDiagnosticField('field', 'Field', $kind, $value, DiagnosticDisclosure::Protected, $truncated),
                DiagnosticPresentationDiagnostic::InvalidDiagnosticTruncationState,
            );
        }
    }

    public function testShapeIsFinalReadonlyAndExact(): void
    {
        $reflection = new ReflectionClass(TrustedDeveloperDiagnosticField::class);
        $properties = $reflection->getProperties();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(['key', 'label', 'kind', 'value', 'disclosure', 'textWasTruncated'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame(['__construct', 'disclosure', 'key', 'kind', 'label', 'textWasTruncated', 'value'], $methodNames);
    }

    private function field(string $key, string $label = 'Field'): TrustedDeveloperDiagnosticField
    {
        return new TrustedDeveloperDiagnosticField($key, $label, DiagnosticValueKind::Text, 'value', DiagnosticDisclosure::Protected, false);
    }

    private function text(string $value, bool $truncated): TrustedDeveloperDiagnosticField
    {
        return new TrustedDeveloperDiagnosticField('field', 'Field', DiagnosticValueKind::Text, $value, DiagnosticDisclosure::Protected, $truncated);
    }

    /** @param callable(): object $operation */
    private function assertFailure(callable $operation, DiagnosticPresentationDiagnostic $diagnostic): void
    {
        try {
            $operation();
            self::fail('Invalid trusted developer field input must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(InvalidDiagnosticProjectionException::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
        }
    }
}
