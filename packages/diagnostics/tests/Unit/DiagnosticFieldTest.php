<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class DiagnosticFieldTest extends TestCase
{
    public function testMetadataIsCopiedWithoutRetainingSchema(): void
    {
        $schema = $this->schema('field', 'Field label', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe);
        $field = new DiagnosticField($schema, 42);

        self::assertSame('field', $field->key());
        self::assertSame('Field label', $field->label());
        self::assertSame(DiagnosticValueKind::Integer, $field->kind());
        self::assertSame(42, $field->value());
        self::assertSame(DiagnosticDisclosure::Safe, $field->disclosure());
        self::assertNull($field->textWasTruncated());
        self::assertFalse((new ReflectionClass(DiagnosticField::class))->hasProperty('schema'));
    }

    public function testIntegerAndBooleanValuesRetainExactNativeIdentity(): void
    {
        $integer = new DiagnosticField($this->schema('integer', 'Integer', DiagnosticValueKind::Integer), PHP_INT_MIN);
        $boolean = new DiagnosticField($this->schema('boolean', 'Boolean', DiagnosticValueKind::Boolean), false);

        self::assertSame(PHP_INT_MIN, $integer->value());
        self::assertNull($integer->textWasTruncated());
        self::assertFalse($boolean->value());
        self::assertNull($boolean->textWasTruncated());
    }

    public function testTextRepresentationIsByteExact(): void
    {
        $cases = [
            '' => '',
            'printable' => 'printable',
            "\\" => '\\\\',
            "\t" => '\\t',
            "\n" => '\\n',
            "\r" => '\\r',
            "\0" => '\\x00',
            "\x7F" => '\\x7F',
            "\x80" => '\\x80',
            "\xFF" => '\\xFF',
            "\xC3\xA9" => '\\xC3\\xA9',
            "\xAB" => '\\xAB',
        ];

        foreach ($cases as $input => $expected) {
            $field = new DiagnosticField($this->schema('text', 'Text', DiagnosticValueKind::Text), $input);
            self::assertSame($expected, $field->value());
            self::assertFalse($field->textWasTruncated());
        }
    }

    public function testExactly512RepresentedBytesDoNotTruncate(): void
    {
        foreach ([str_repeat('A', 512), str_repeat("\0", 128)] as $input) {
            $field = new DiagnosticField($this->schema('text', 'Text', DiagnosticValueKind::Text), $input);
            self::assertSame(512, strlen((string) $field->value()));
            self::assertFalse($field->textWasTruncated());
        }
    }

    public function testLongTextTruncatesAtCompleteTokenBoundaries(): void
    {
        $literal = new DiagnosticField($this->schema('literal', 'Literal', DiagnosticValueKind::Text), str_repeat('A', 513));
        $slashes = new DiagnosticField($this->schema('slashes', 'Slashes', DiagnosticValueKind::Text), str_repeat('\\', 257));
        $binary = new DiagnosticField($this->schema('binary', 'Binary', DiagnosticValueKind::Text), str_repeat("\0", 129));

        self::assertSame(str_repeat('A', 498) . '...[truncated]', $literal->value());
        self::assertSame(str_repeat('\\\\', 249) . '...[truncated]', $slashes->value());
        self::assertSame(str_repeat('\\x00', 124) . '...[truncated]', $binary->value());

        foreach ([$literal, $slashes, $binary] as $field) {
            self::assertTrue($field->textWasTruncated());
            self::assertLessThanOrEqual(512, strlen((string) $field->value()));
            self::assertStringEndsWith('...[truncated]', (string) $field->value());
            self::assertDoesNotMatchRegularExpression('/(?:\\\\x[0-9A-F]{0,1}|\\\\)$/D', (string) $field->value());
        }
    }

    public function testVeryLargeInputProducesTheSameBoundedResult(): void
    {
        $schema = $this->schema('text', 'Text', DiagnosticValueKind::Text);
        $bounded = new DiagnosticField($schema, str_repeat('A', 513));
        $large = new DiagnosticField($schema, str_repeat('A', 1_000_000));

        self::assertSame($bounded->value(), $large->value());
        self::assertSame($bounded->textWasTruncated(), $large->textWasTruncated());
    }

    public function testCrossKindScalarMismatchesUseExactFailure(): void
    {
        $cases = [
            [$this->schema('text', 'Text', DiagnosticValueKind::Text), 1, 'Diagnostic field "text" requires text; received int.'],
            [$this->schema('text', 'Text', DiagnosticValueKind::Text), false, 'Diagnostic field "text" requires text; received bool.'],
            [$this->schema('integer', 'Integer', DiagnosticValueKind::Integer), '1', 'Diagnostic field "integer" requires integer; received string.'],
            [$this->schema('integer', 'Integer', DiagnosticValueKind::Integer), true, 'Diagnostic field "integer" requires integer; received bool.'],
            [$this->schema('boolean', 'Boolean', DiagnosticValueKind::Boolean), 'true', 'Diagnostic field "boolean" requires boolean; received string.'],
            [$this->schema('boolean', 'Boolean', DiagnosticValueKind::Boolean), 1, 'Diagnostic field "boolean" requires boolean; received int.'],
        ];

        foreach ($cases as [$schema, $value, $message]) {
            try {
                new DiagnosticField($schema, $value);
                self::fail('A cross-kind scalar must fail.');
            } catch (Throwable $exception) {
                self::assertInstanceOf(InvalidDiagnosticValueException::class, $exception);
                self::assertSame(DiagnosticsDiagnostic::ContextValueKindMismatch->value, $exception->diagnosticId());
                self::assertSame(DiagnosticsDiagnostic::ContextValueKindMismatch->title(), $exception->diagnosticTitle());
                self::assertSame(DiagnosticsDiagnostic::ContextValueKindMismatch->remediation(), $exception->diagnosticRemediation());
                self::assertSame($message, $exception->getMessage());
                self::assertSame(0, $exception->getCode());
                self::assertNull($exception->getPrevious());
            }
        }
    }

    public function testShapeIsFinalReadonlyAndExact(): void
    {
        $reflection = new ReflectionClass(DiagnosticField::class);
        $properties = $reflection->getProperties();

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(
            ['key', 'label', 'kind', 'value', 'disclosure', 'textWasTruncated'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties),
        );
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }
        self::assertSame(
            ['__construct', 'disclosure', 'key', 'kind', 'label', 'textWasTruncated', 'value'],
            $this->sortedMethodNames($reflection->getMethods(ReflectionMethod::IS_PUBLIC)),
        );

        $source = file_get_contents(__DIR__ . '/../../src/Context/DiagnosticField.php');
        self::assertIsString($source);
        self::assertSame(1, substr_count($source, 'private string|int|bool $value;'));
    }

    private function schema(
        string $key,
        string $label,
        DiagnosticValueKind $kind,
        DiagnosticDisclosure $disclosure = DiagnosticDisclosure::Protected,
    ): DiagnosticFieldSchema {
        return new DiagnosticFieldSchema($key, $label, $kind, $disclosure);
    }

    /** @param list<ReflectionMethod> $methods
     * @return list<string>
     */
    private function sortedMethodNames(array $methods): array
    {
        $names = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        return $names;
    }
}
