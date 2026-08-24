<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Tests\Unit;

use FilesystemIterator;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Diagnostics\Diagnostic\DiagnosticsDiagnostic;
use Kongono\Diagnostics\Exception\DiagnosticsException;
use Kongono\Diagnostics\Exception\ExceptionInterface;
use Kongono\Diagnostics\Exception\InvalidDiagnosticValueException;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class StructuralValueTest extends TestCase
{
    /** @var array<class-string, ApiStatus> */
    private const EXPECTED_STATUS = [
        DiagnosticContext::class => ApiStatus::Public,
        DiagnosticContextSchema::class => ApiStatus::Public,
        DiagnosticDisclosure::class => ApiStatus::Public,
        DiagnosticField::class => ApiStatus::Public,
        DiagnosticFieldSchema::class => ApiStatus::Public,
        DiagnosticValueKind::class => ApiStatus::Public,
        DiagnosticsDiagnostic::class => ApiStatus::Internal,
        DiagnosticsException::class => ApiStatus::Internal,
        ExceptionInterface::class => ApiStatus::Public,
        InvalidDiagnosticValueException::class => ApiStatus::Experimental,
    ];

    public function testDiagnosticCatalogIsExactOrderedAndGloballyWellFormed(): void
    {
        $expected = [
            ['InvalidFieldKey', 'KGN-DIAGNOSTICS-1001', 'Diagnostic field key violates the canonical key contract', 'Use non-empty lowercase ASCII snake case containing at most 64 bytes'],
            ['InvalidFieldLabel', 'KGN-DIAGNOSTICS-1002', 'Diagnostic field label violates the fixed-label contract', 'Use non-empty valid UTF-8 containing at most 96 bytes'],
            ['InvalidOperation', 'KGN-DIAGNOSTICS-1003', 'Diagnostic context operation violates its prose contract', 'Use non-empty valid UTF-8 containing at most 160 bytes'],
            ['InvalidInvariant', 'KGN-DIAGNOSTICS-1004', 'Diagnostic context invariant violates its prose contract', 'Use non-empty valid UTF-8 containing at most 256 bytes'],
            ['FieldSchemasNotList', 'KGN-DIAGNOSTICS-1005', 'Diagnostic field schemas are not a zero-based consecutive list', 'Reindex field schemas as a PHP list'],
            ['InvalidFieldSchemaEntry', 'KGN-DIAGNOSTICS-1006', 'Diagnostic schema list contains a non-field-schema entry', 'Replace the reported entry with a DiagnosticFieldSchema'],
            ['TooManyFieldSchemas', 'KGN-DIAGNOSTICS-1007', 'Diagnostic context schema exceeds the field limit', 'Define at most 16 field schemas'],
            ['DuplicateFieldSchemaKey', 'KGN-DIAGNOSTICS-1008', 'Diagnostic context schema contains a duplicate field key', 'Keep each canonical field key exactly once'],
            ['InvalidContextValueKey', 'KGN-DIAGNOSTICS-1009', 'Diagnostic context values contain an unapproved field key', 'Supply values only for exact keys in the closed schema'],
            ['MissingRequiredContextValue', 'KGN-DIAGNOSTICS-1010', 'Diagnostic context is missing a required field value', 'Supply every value required by the closed schema'],
            ['ContextValueKindMismatch', 'KGN-DIAGNOSTICS-1011', 'Diagnostic field value does not match its schema kind', 'Supply the exact string, integer, or boolean type required by the field schema'],
        ];
        $actual = array_map(
            static fn(DiagnosticsDiagnostic $diagnostic): array => [
                $diagnostic->name,
                $diagnostic->value,
                $diagnostic->title(),
                $diagnostic->remediation(),
            ],
            DiagnosticsDiagnostic::cases(),
        );
        $identifiers = array_column($actual, 1);

        self::assertSame($expected, $actual);
        self::assertCount(11, array_unique($identifiers));
        foreach ($identifiers as $identifier) {
            self::assertMatchesRegularExpression('/^KGN-DIAGNOSTICS-1\d{3}$/D', $identifier);
        }
    }

    public function testMarkerBaseAndConcreteShapesAreExact(): void
    {
        $marker = new ReflectionClass(ExceptionInterface::class);
        $markerMethods = array_values(array_filter(
            $marker->getMethods(),
            static fn(ReflectionMethod $method): bool => ExceptionInterface::class === $method->getDeclaringClass()->getName(),
        ));
        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        self::assertSame(
            ['diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            $this->sortedMethodNames($markerMethods),
        );
        foreach ($markerMethods as $method) {
            self::assertSame('string', (string) $method->getReturnType());
            self::assertSame([], $method->getParameters());
        }

        $base = new ReflectionClass(DiagnosticsException::class);
        $baseProperties = array_values(array_filter(
            $base->getProperties(),
            static fn(\ReflectionProperty $property): bool => DiagnosticsException::class === $property->getDeclaringClass()->getName(),
        ));
        $baseMethods = array_values(array_filter(
            $base->getMethods(),
            static fn(ReflectionMethod $method): bool => DiagnosticsException::class === $method->getDeclaringClass()->getName(),
        ));
        self::assertTrue($base->isAbstract());
        self::assertCount(1, $baseProperties);
        self::assertSame('diagnostic', $baseProperties[0]->getName());
        self::assertTrue($baseProperties[0]->isPrivate());
        self::assertTrue($baseProperties[0]->isReadOnly());
        self::assertSame(DiagnosticsDiagnostic::class, (string) $baseProperties[0]->getType());
        self::assertSame(
            ['__construct', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'],
            $this->sortedMethodNames($baseMethods),
        );
        foreach ($baseMethods as $method) {
            self::assertTrue($method->isFinal());
            self::assertTrue($method->isPublic());
        }

        $parameters = $base->getConstructor()?->getParameters();
        self::assertNotNull($parameters);
        self::assertSame(
            [DiagnosticsDiagnostic::class, 'string', '?Throwable'],
            array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $parameters),
        );
        self::assertNull($parameters[2]->getDefaultValue());

        $concrete = new ReflectionClass(InvalidDiagnosticValueException::class);
        $parent = $concrete->getParentClass();
        self::assertTrue($concrete->isFinal());
        self::assertInstanceOf(ReflectionClass::class, $parent);
        self::assertSame(DiagnosticsException::class, $parent->getName());
        self::assertSame([], array_values(array_filter(
            $concrete->getProperties(),
            static fn(\ReflectionProperty $property): bool => InvalidDiagnosticValueException::class === $property->getDeclaringClass()->getName(),
        )));
        self::assertSame([], array_values(array_filter(
            $concrete->getMethods(),
            static fn(ReflectionMethod $method): bool => InvalidDiagnosticValueException::class === $method->getDeclaringClass()->getName(),
        )));
    }

    public function testPreviousIdentityCodeAndPackageSeparationAreExact(): void
    {
        $cause = new RuntimeException('cause');
        $exception = new InvalidDiagnosticValueException(DiagnosticsDiagnostic::InvalidFieldKey, 'message', $cause);

        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertFalse(is_a(ExceptionInterface::class, KernelExceptionInterface::class, true));
        self::assertFalse(is_a(ExceptionInterface::class, GraphExceptionInterface::class, true));
        self::assertFalse(is_a(ExceptionInterface::class, DependencyInjectionExceptionInterface::class, true));
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
        self::assertFalse((new ReflectionClass(ExceptionInterface::class))->hasMethod('diagnosticContext'));
    }

    public function testAllProductionTypesCarryExactApiStatusAndNoStaticMutableState(): void
    {
        foreach (self::EXPECTED_STATUS as $type => $status) {
            $reflection = new ReflectionClass($type);
            $attributes = $reflection->getAttributes(Api::class);

            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
            foreach ($reflection->getProperties() as $property) {
                if ($type === $property->getDeclaringClass()->getName()) {
                    self::assertFalse($property->isStatic(), sprintf('%s must not declare static state.', $type));
                }
            }
        }
    }

    public function testProductionSourceUsesOnlyAuthorizedApiMetadataAndNoAmbientCapabilities(): void
    {
        $sourceDirectory = __DIR__ . '/../../src';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
        );
        $combined = '';

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            $combined .= $source;

            preg_match_all('/^use Kongono\\\\([^;]+);$/m', $source, $matches);
            foreach ($matches[1] as $import) {
                self::assertTrue(
                    str_starts_with($import, 'Diagnostics\\')
                    || in_array($import, ['ApiMetadata\\Api', 'ApiMetadata\\ApiStatus'], true),
                    sprintf('Unexpected framework import %s.', $import),
                );
            }
        }

        foreach ([
            'Kongono\\ApplicationGraph',
            'Kongono\\DependencyInjection',
            'Kongono\\Kernel',
            'getenv(',
            '$_ENV',
            '$_SERVER',
            'random_',
            'microtime(',
            'file_get_contents(',
            'fopen(',
            'Logger',
            'Telemetry',
            'OpenTelemetry',
            'ArtificialIntelligence',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $combined);
        }
        self::assertStringNotContainsString('static $', $combined);
        self::assertStringNotContainsString('cache', strtolower($combined));

        $manifest = json_decode(
            (string) file_get_contents(__DIR__ . '/../../composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($manifest);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
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
