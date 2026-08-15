<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnostic;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticField;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class TrustedDeveloperDiagnosticTest extends TestCase
{
    public function testAllOwnerIdentifierMappingsIncludingNoncatalogIdsAreAccepted(): void
    {
        foreach ([
            [DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-9999'],
            [DeveloperDiagnosticOwner::ApplicationGraph, 'KGN-GRAPH-0000'],
            [DeveloperDiagnosticOwner::DependencyInjection, 'KGN-DI-4321'],
        ] as [$owner, $identifier]) {
            $diagnostic = $this->diagnostic($owner, $identifier);
            self::assertSame($owner, $diagnostic->owner());
            self::assertSame($identifier, $diagnostic->identifier());
        }

        foreach ([
            [DeveloperDiagnosticOwner::Kernel, 'KGN-GRAPH-1001'],
            [DeveloperDiagnosticOwner::ApplicationGraph, 'KGN-GRAPH-123'],
            [DeveloperDiagnosticOwner::DependencyInjection, 'KGN-DI-12A4'],
            [DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-１２３４'],
        ] as [$owner, $identifier]) {
            $this->assertFailure(
                fn(): TrustedDeveloperDiagnostic => $this->diagnostic($owner, $identifier),
                DiagnosticPresentationDiagnostic::InvalidDiagnosticIdentifier,
            );
        }
    }

    public function testExactProseBoundsAndUtf8AreEnforced(): void
    {
        $diagnostic = new TrustedDeveloperDiagnostic(
            DeveloperDiagnosticOwner::Kernel,
            'KGN-KERNEL-1001',
            str_repeat('t', 160),
            str_repeat('o', 160),
            str_repeat('i', 256),
            str_repeat('r', 256),
            [],
        );
        self::assertSame(160, strlen($diagnostic->title()));
        self::assertSame(160, strlen($diagnostic->operation()));
        self::assertSame(256, strlen($diagnostic->violatedInvariant()));
        self::assertSame(256, strlen($diagnostic->remediation()));

        foreach ([['title', '', DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle], ['title', "\xFF", DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle], ['title', str_repeat('t', 161), DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle], ['operation', '', DiagnosticPresentationDiagnostic::InvalidDiagnosticOperation], ['operation', str_repeat('o', 161), DiagnosticPresentationDiagnostic::InvalidDiagnosticOperation], ['invariant', "\xFF", DiagnosticPresentationDiagnostic::InvalidDiagnosticInvariant], ['invariant', str_repeat('i', 257), DiagnosticPresentationDiagnostic::InvalidDiagnosticInvariant], ['remediation', '', DiagnosticPresentationDiagnostic::InvalidDiagnosticRemediation], ['remediation', str_repeat('r', 257), DiagnosticPresentationDiagnostic::InvalidDiagnosticRemediation]] as [$subject, $value, $expected]) {
            $this->assertFailure(fn(): TrustedDeveloperDiagnostic => $this->diagnosticWithProse($subject, $value), $expected);
        }
    }

    public function testFieldListEntryMaximumDuplicateAndOrderRulesAreExact(): void
    {
        $first = $this->field('first');
        $second = $this->field('second');
        $ordered = $this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [$second, $first]);
        self::assertSame(['second', 'first'], array_map(static fn(TrustedDeveloperDiagnosticField $field): string => $field->key(), $ordered->fields()));

        $this->assertFailure(
            fn(): TrustedDeveloperDiagnostic => $this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [1 => $first]),
            DiagnosticPresentationDiagnostic::DiagnosticFieldsNotList,
        );
        $this->assertFailure(
            fn(): TrustedDeveloperDiagnostic => $this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [$first, 'invalid']),
            DiagnosticPresentationDiagnostic::InvalidDiagnosticFieldEntry,
        );
        $this->assertFailure(
            fn(): TrustedDeveloperDiagnostic => $this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', array_fill(0, 17, $first)),
            DiagnosticPresentationDiagnostic::TooManyDiagnosticFields,
        );
        $this->assertFailure(
            fn(): TrustedDeveloperDiagnostic => $this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', [$first, $this->field('first')]),
            DiagnosticPresentationDiagnostic::DuplicateDiagnosticFieldKey,
        );
    }

    public function testCallerReferencesAndReturnedArraysCannotMutateMembership(): void
    {
        $first = $this->field('first');
        $second = $this->field('second');
        $slot = $first;
        $fields = [&$slot, $second];
        $diagnostic = $this->diagnostic(DeveloperDiagnosticOwner::Kernel, 'KGN-KERNEL-1001', $fields);
        $slot = $this->field('changed');
        $fields = [];

        self::assertSame([$first, $second], $diagnostic->fields());
        $returned = $diagnostic->fields();
        $returned[0] = $this->field('returned');
        self::assertSame([$first, $second], $diagnostic->fields());
        self::assertNotSame($returned, $diagnostic->fields());
    }

    public function testShapeIsFinalReadonlyAndExact(): void
    {
        $reflection = new ReflectionClass(TrustedDeveloperDiagnostic::class);
        $properties = $reflection->getProperties();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(['owner', 'identifier', 'title', 'operation', 'violatedInvariant', 'remediation', 'fields'], array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties));
        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame(['__construct', 'fields', 'identifier', 'operation', 'owner', 'remediation', 'title', 'violatedInvariant'], $methodNames);
    }

    /** @param array<array-key, mixed> $fields */
    private function diagnostic(
        DeveloperDiagnosticOwner $owner,
        string $identifier,
        array $fields = [],
    ): TrustedDeveloperDiagnostic {
        return new TrustedDeveloperDiagnostic($owner, $identifier, 'Title', 'Operation', 'Invariant', 'Remediation', $fields);
    }

    private function diagnosticWithProse(string $subject, string $value): TrustedDeveloperDiagnostic
    {
        return new TrustedDeveloperDiagnostic(
            DeveloperDiagnosticOwner::Kernel,
            'KGN-KERNEL-1001',
            'title' === $subject ? $value : 'Title',
            'operation' === $subject ? $value : 'Operation',
            'invariant' === $subject ? $value : 'Invariant',
            'remediation' === $subject ? $value : 'Remediation',
            [],
        );
    }

    private function field(string $key): TrustedDeveloperDiagnosticField
    {
        return new TrustedDeveloperDiagnosticField($key, ucfirst($key), DiagnosticValueKind::Text, 'value', DiagnosticDisclosure::Protected, false);
    }

    /** @param callable(): object $operation */
    private function assertFailure(callable $operation, DiagnosticPresentationDiagnostic $diagnostic): void
    {
        try {
            $operation();
            self::fail('Invalid trusted developer diagnostic input must fail.');
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
