<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Throwable;

final class DiagnosticContextTest extends TestCase
{
    public function testEmptyContextAndOptionalOmissionAreExact(): void
    {
        $empty = new DiagnosticContext(new DiagnosticContextSchema('Operation', 'Invariant'));
        $optional = $this->field('optional', DiagnosticValueKind::Text, false);
        $context = new DiagnosticContext(new DiagnosticContextSchema('Optional operation', 'Optional invariant', [$optional]));

        self::assertSame('Operation', $empty->operation());
        self::assertSame('Invariant', $empty->violatedInvariant());
        self::assertSame([], $empty->fields());
        self::assertSame([], $context->fields());
    }

    public function testFieldsUseSchemaOrderAndExactRepresentedValues(): void
    {
        $schema = new DiagnosticContextSchema('Operation', 'Invariant', [
            $this->field('text', DiagnosticValueKind::Text, true, DiagnosticDisclosure::Protected),
            $this->field('count', DiagnosticValueKind::Integer, true, DiagnosticDisclosure::Safe),
            $this->field('enabled', DiagnosticValueKind::Boolean, true, DiagnosticDisclosure::Safe),
            $this->field('optional', DiagnosticValueKind::Text, false),
        ]);
        $context = new DiagnosticContext($schema, [
            'enabled' => true,
            'count' => 3,
            'text' => "A\n",
        ]);
        $fields = $context->fields();

        self::assertSame(['text', 'count', 'enabled'], array_map(static fn($field): string => $field->key(), $fields));
        self::assertSame(['A\\n', 3, true], array_map(static fn($field): string|int|bool => $field->value(), $fields));
        self::assertSame(
            [DiagnosticValueKind::Text, DiagnosticValueKind::Integer, DiagnosticValueKind::Boolean],
            array_map(static fn($field): DiagnosticValueKind => $field->kind(), $fields),
        );
        self::assertSame(
            [DiagnosticDisclosure::Protected, DiagnosticDisclosure::Safe, DiagnosticDisclosure::Safe],
            array_map(static fn($field): DiagnosticDisclosure => $field->disclosure(), $fields),
        );
        self::assertSame([false, null, null], array_map(static fn($field): ?bool => $field->textWasTruncated(), $fields));
    }

    public function testInvalidKeysAreSelectedDeterministicallyBeforeMissingValues(): void
    {
        $schema = new DiagnosticContextSchema('Operation', 'Invariant', [$this->field('required', DiagnosticValueKind::Text)]);
        $this->assertFailure(
            static fn(): DiagnosticContext => new DiagnosticContext($schema, ['z_unknown' => 'z', -2 => 'x', 5 => 'y', 'a_unknown' => 'a']),
            DiagnosticsDiagnostic::InvalidContextValueKey,
            'Diagnostic context value key must be a schema-approved string; received integer key -2.',
        );
        $this->assertFailure(
            static fn(): DiagnosticContext => new DiagnosticContext($schema, ['z_unknown' => 'z', 'a_unknown' => 'a']),
            DiagnosticsDiagnostic::InvalidContextValueKey,
            'Diagnostic context value key "a_unknown" is not present in the closed schema.',
        );
    }

    public function testFirstMissingRequiredValueUsesSchemaOrder(): void
    {
        $schema = new DiagnosticContextSchema('Operation', 'Invariant', [
            $this->field('first', DiagnosticValueKind::Text),
            $this->field('second', DiagnosticValueKind::Text),
        ]);
        $this->assertFailure(
            static fn(): DiagnosticContext => new DiagnosticContext($schema),
            DiagnosticsDiagnostic::MissingRequiredContextValue,
            'Diagnostic context is missing required field value "first".',
        );
        $this->assertFailure(
            static fn(): DiagnosticContext => new DiagnosticContext($schema, ['first' => 'value']),
            DiagnosticsDiagnostic::MissingRequiredContextValue,
            'Diagnostic context is missing required field value "second".',
        );
    }

    public function testAllInvalidNativeKindsUseExactKindMismatch(): void
    {
        $schema = new DiagnosticContextSchema('Operation', 'Invariant', [$this->field('text', DiagnosticValueKind::Text)]);
        $invalidValues = [null, 1.5, [], new stdClass(), DiagnosticValueKind::Text, STDIN, static fn(): string => 'value'];

        foreach ($invalidValues as $value) {
            $this->assertFailure(
                static fn(): DiagnosticContext => new DiagnosticContext($schema, ['text' => $value]),
                DiagnosticsDiagnostic::ContextValueKindMismatch,
                sprintf('Diagnostic field "text" requires text; received %s.', get_debug_type($value)),
            );
        }

        foreach ([[DiagnosticValueKind::Integer, '1'], [DiagnosticValueKind::Boolean, 1]] as [$kind, $value]) {
            $typedSchema = new DiagnosticContextSchema('Operation', 'Invariant', [$this->field('typed', $kind)]);
            $this->assertFailure(
                static fn(): DiagnosticContext => new DiagnosticContext($typedSchema, ['typed' => $value]),
                DiagnosticsDiagnostic::ContextValueKindMismatch,
                sprintf('Diagnostic field "typed" requires %s; received %s.', $kind->value, get_debug_type($value)),
            );
        }
    }

    public function testInputReferencesReturnedArraysAndContextsAreIndependent(): void
    {
        $schema = new DiagnosticContextSchema('Operation', 'Invariant', [$this->field('text', DiagnosticValueKind::Text)]);
        $slot = 'before';
        $values = ['text' => &$slot];
        $first = new DiagnosticContext($schema, $values);
        $slot = 'after';
        $values['text'] = 'changed';
        $second = new DiagnosticContext($schema, ['text' => 'second']);

        self::assertSame('before', $first->fields()[0]->value());
        self::assertSame('second', $second->fields()[0]->value());
        self::assertNotSame($first->fields()[0], $second->fields()[0]);
        self::assertEquals($first->fields(), $first->fields());
        $returned = $first->fields();
        $returned = [];
        self::assertSame('before', $first->fields()[0]->value());
        self::assertSame([], $returned);
    }

    public function testShapeIsFinalReadonlyAndExact(): void
    {
        $reflection = new ReflectionClass(DiagnosticContext::class);
        $properties = $reflection->getProperties();

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(
            ['operation', 'violatedInvariant', 'fields'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties),
        );
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }
        self::assertSame(
            ['__construct', 'fields', 'operation', 'violatedInvariant'],
            $this->sortedMethodNames($reflection->getMethods(ReflectionMethod::IS_PUBLIC)),
        );
        self::assertFalse($reflection->hasProperty('schema'));
    }

    private function field(
        string $key,
        DiagnosticValueKind $kind,
        bool $required = true,
        DiagnosticDisclosure $disclosure = DiagnosticDisclosure::Protected,
    ): DiagnosticFieldSchema {
        return new DiagnosticFieldSchema($key, ucfirst($key), $kind, $disclosure, $required);
    }

    /** @param callable(): object $operation */
    private function assertFailure(
        callable $operation,
        DiagnosticsDiagnostic $diagnostic,
        string $message,
    ): void {
        try {
            $operation();
            self::fail('Invalid diagnostic context input must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(InvalidDiagnosticValueException::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($message, $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
        }
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
