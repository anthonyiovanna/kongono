<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\ExceptionInterface;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class DiagnosticFieldSchemaTest extends TestCase
{
    public function testAccessorsPreserveExactMetadataAndRequiredDefault(): void
    {
        $required = new DiagnosticFieldSchema(
            'field_2',
            'Field label',
            DiagnosticValueKind::Integer,
            DiagnosticDisclosure::Safe,
        );
        $optional = new DiagnosticFieldSchema(
            'other',
            'Other label',
            DiagnosticValueKind::Text,
            DiagnosticDisclosure::Protected,
            false,
        );

        self::assertSame('field_2', $required->key());
        self::assertSame('Field label', $required->label());
        self::assertSame(DiagnosticValueKind::Integer, $required->kind());
        self::assertSame(DiagnosticDisclosure::Safe, $required->disclosure());
        self::assertTrue($required->isRequired());
        self::assertFalse($optional->isRequired());
    }

    public function testCanonicalKeysAtAuthorizedBoundariesAreAccepted(): void
    {
        foreach (['a', str_repeat('a', 64), 'a2', 'field_2'] as $key) {
            self::assertSame($key, $this->schema($key)->key());
        }
    }

    public function testInvalidKeysAreRejectedWithExactFailure(): void
    {
        foreach (['', 'Upper', '_leading', 'trailing_', 'double__underscore', '2leading', 'punctuation!', "na\xC3\xAFve", "nul\0key", str_repeat('a', 65)] as $key) {
            $this->assertFailure(
                static fn(): DiagnosticFieldSchema => new DiagnosticFieldSchema(
                    $key,
                    'Label',
                    DiagnosticValueKind::Text,
                    DiagnosticDisclosure::Protected,
                ),
                DiagnosticsDiagnostic::InvalidFieldKey,
                'Diagnostic field key must match lowercase ASCII snake case and contain at most 64 bytes.',
            );
        }
    }

    public function testUtf8LabelsAtAuthorizedBoundaryAreAccepted(): void
    {
        foreach (['Label', str_repeat('a', 96), str_repeat("\xC3\xA9", 48)] as $label) {
            self::assertSame($label, $this->schema('field', $label)->label());
        }
    }

    public function testInvalidLabelsAreRejectedAfterKeyValidation(): void
    {
        foreach (['', "\xFF", str_repeat('a', 97)] as $label) {
            $this->assertFailure(
                static fn(): DiagnosticFieldSchema => new DiagnosticFieldSchema(
                    'field',
                    $label,
                    DiagnosticValueKind::Text,
                    DiagnosticDisclosure::Protected,
                ),
                DiagnosticsDiagnostic::InvalidFieldLabel,
                'Diagnostic field label must be non-empty valid UTF-8 and contain at most 96 bytes.',
            );
        }

        $this->assertFailure(
            static fn(): DiagnosticFieldSchema => new DiagnosticFieldSchema(
                '',
                '',
                DiagnosticValueKind::Text,
                DiagnosticDisclosure::Protected,
            ),
            DiagnosticsDiagnostic::InvalidFieldKey,
            'Diagnostic field key must match lowercase ASCII snake case and contain at most 64 bytes.',
        );
    }

    public function testShapeIsFinalReadonlyAndExact(): void
    {
        $reflection = new ReflectionClass(DiagnosticFieldSchema::class);
        $properties = $reflection->getProperties();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(
            ['key', 'label', 'kind', 'disclosure', 'required'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties),
        );
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }
        self::assertSame(
            ['__construct', 'disclosure', 'isRequired', 'key', 'kind', 'label'],
            $this->sortedMethodNames($methods),
        );
    }

    private function schema(string $key, string $label = 'Label'): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema(
            $key,
            $label,
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
            self::fail('Invalid diagnostic schema input must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertNotInstanceOf(KernelExceptionInterface::class, $exception);
            self::assertNotInstanceOf(GraphExceptionInterface::class, $exception);
            self::assertNotInstanceOf(DependencyInjectionExceptionInterface::class, $exception);
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
