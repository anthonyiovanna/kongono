<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class DiagnosticContextSchemaTest extends TestCase
{
    public function testZeroThroughSixteenSchemasPreserveExactOrder(): void
    {
        for ($count = 0; $count <= 16; ++$count) {
            $fields = [];
            for ($index = 0; $index < $count; ++$index) {
                $fields[] = $this->field('field_' . $index);
            }

            $schema = new DiagnosticContextSchema('Operation', 'Invariant', $fields);
            self::assertSame('Operation', $schema->operation());
            self::assertSame('Invariant', $schema->violatedInvariant());
            self::assertSame($fields, $schema->fields());
        }
    }

    public function testProseAtExactByteBoundsIsAccepted(): void
    {
        $schema = new DiagnosticContextSchema(str_repeat('o', 160), str_repeat('i', 256));

        self::assertSame(160, strlen($schema->operation()));
        self::assertSame(256, strlen($schema->violatedInvariant()));
    }

    public function testInvalidOperationAndInvariantAreRejectedInOrder(): void
    {
        foreach (['', "\xFF", str_repeat('o', 161)] as $operation) {
            $this->assertFailure(
                static fn(): DiagnosticContextSchema => new DiagnosticContextSchema($operation, 'Invariant'),
                DiagnosticsDiagnostic::InvalidOperation,
                'Diagnostic context operation must be non-empty valid UTF-8 and contain at most 160 bytes.',
            );
        }
        foreach (['', "\xFF", str_repeat('i', 257)] as $invariant) {
            $this->assertFailure(
                static fn(): DiagnosticContextSchema => new DiagnosticContextSchema('Operation', $invariant),
                DiagnosticsDiagnostic::InvalidInvariant,
                'Diagnostic context invariant must be non-empty valid UTF-8 and contain at most 256 bytes.',
            );
        }
        $this->assertFailure(
            static fn(): DiagnosticContextSchema => new DiagnosticContextSchema('', ''),
            DiagnosticsDiagnostic::InvalidOperation,
            'Diagnostic context operation must be non-empty valid UTF-8 and contain at most 160 bytes.',
        );
    }

    public function testInvalidCollectionsMapToExactFailures(): void
    {
        $field = $this->field('field');
        $this->assertFailure(
            static fn(): DiagnosticContextSchema => new DiagnosticContextSchema('Operation', 'Invariant', [1 => $field]),
            DiagnosticsDiagnostic::FieldSchemasNotList,
            'Diagnostic field schemas must be supplied as a zero-based consecutive list.',
        );
        $this->assertFailure(
            static fn(): DiagnosticContextSchema => new DiagnosticContextSchema('Operation', 'Invariant', [$field, 'invalid']),
            DiagnosticsDiagnostic::InvalidFieldSchemaEntry,
            'Diagnostic field schema at position 1 must be a DiagnosticFieldSchema.',
        );
        $this->assertFailure(
            static fn(): DiagnosticContextSchema => new DiagnosticContextSchema('Operation', 'Invariant', array_fill(0, 17, 'invalid')),
            DiagnosticsDiagnostic::TooManyFieldSchemas,
            'Diagnostic context schema may contain at most 16 fields; received 17.',
        );
        $this->assertFailure(
            fn(): DiagnosticContextSchema => new DiagnosticContextSchema('Operation', 'Invariant', [$field, $this->field('field')]),
            DiagnosticsDiagnostic::DuplicateFieldSchemaKey,
            'Diagnostic context schema contains duplicate field key "field".',
        );
    }

    public function testDistinctKeysAndAllArrayBoundariesAreDetached(): void
    {
        $first = $this->field('field');
        $second = $this->field('field_0');
        $slot = $first;
        $caller = [&$slot, $second];
        $schema = new DiagnosticContextSchema('Operation', 'Invariant', $caller);
        $slot = $this->field('changed');
        $caller = [];

        self::assertSame([$first, $second], $schema->fields());
        $returned = $schema->fields();
        $returned[0] = $this->field('returned_change');
        self::assertSame([$first, $second], $schema->fields());
        self::assertNotSame($returned, $schema->fields());
    }

    public function testShapeIsFinalReadonlyAndExact(): void
    {
        $reflection = new ReflectionClass(DiagnosticContextSchema::class);
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
    }

    private function field(string $key): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema(
            $key,
            'Label',
            DiagnosticValueKind::Text,
            DiagnosticDisclosure::Protected,
        );
    }

    /** @param callable(): object $operation */
    private function assertFailure(
        callable $operation,
        DiagnosticsDiagnostic $diagnostic,
        string $message,
    ): void {
        try {
            $operation();
            self::fail('Invalid diagnostic context schema input must fail.');
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
