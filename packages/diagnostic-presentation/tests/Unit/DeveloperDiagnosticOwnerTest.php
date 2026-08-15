<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use BackedEnum;
use Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner;
use PHPUnit\Framework\TestCase;
use ReflectionEnum;

final class DeveloperDiagnosticOwnerTest extends TestCase
{
    public function testVocabularyOrderNamesValuesAndBackingTypeAreExact(): void
    {
        self::assertSame(
            [
                ['Kernel', 'kernel'],
                ['ApplicationGraph', 'application_graph'],
                ['DependencyInjection', 'dependency_injection'],
            ],
            array_map(
                static fn(DeveloperDiagnosticOwner $case): array => [$case->name, $case->value],
                DeveloperDiagnosticOwner::cases(),
            ),
        );
        self::assertInstanceOf(BackedEnum::class, DeveloperDiagnosticOwner::Kernel);
        self::assertSame(DeveloperDiagnosticOwner::ApplicationGraph, DeveloperDiagnosticOwner::from('application_graph'));
    }

    public function testEnumDeclaresNoMethodsPropertiesOrConstantsBeyondCases(): void
    {
        $reflection = new ReflectionEnum(DeveloperDiagnosticOwner::class);
        $declaredMethods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(\ReflectionMethod $method): bool => !in_array($method->getName(), ['cases', 'from', 'tryFrom'], true),
        ));

        self::assertTrue($reflection->isEnum());
        self::assertSame('string', $reflection->getBackingType()?->getName());
        self::assertSame([], $declaredMethods);
        self::assertSame(
            ['name', 'value'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $reflection->getProperties()),
        );
        self::assertSame(
            ['Kernel', 'ApplicationGraph', 'DependencyInjection'],
            array_map(
                static fn(\ReflectionClassConstant $constant): string => $constant->getName(),
                $reflection->getReflectionConstants(),
            ),
        );
    }
}
