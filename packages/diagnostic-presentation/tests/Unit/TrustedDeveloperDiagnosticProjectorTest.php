<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Tests\Unit;

use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DiagnosticPresentation\Developer\DeveloperDiagnosticOwner;
use Kongono\DiagnosticPresentation\Developer\TrustedDeveloperDiagnosticProjector;
use Kongono\DiagnosticPresentation\Diagnostic\DiagnosticPresentationDiagnostic;
use Kongono\DiagnosticPresentation\Exception\InvalidDiagnosticProjectionException;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use RuntimeException;
use Throwable;

final class TrustedDeveloperDiagnosticProjectorTest extends TestCase
{
    public function testOwnerDerivationForAllThreeMarkerFamiliesIsExact(): void
    {
        $projector = new TrustedDeveloperDiagnosticProjector();
        $context = $this->context([]);

        self::assertSame(DeveloperDiagnosticOwner::Kernel, $projector->project($this->kernelSource($context, 'KGN-KERNEL-1001'))->owner());
        self::assertSame(DeveloperDiagnosticOwner::ApplicationGraph, $projector->project($this->graphSource($context, 'KGN-GRAPH-1001'))->owner());
        self::assertSame(DeveloperDiagnosticOwner::DependencyInjection, $projector->project($this->dependencyInjectionSource($context, 'KGN-DI-1001'))->owner());
    }

    public function testMetadataContextAndMixedFieldsAreCopiedExactlyInCanonicalOrder(): void
    {
        $context = new DiagnosticContext(
            new DiagnosticContextSchema('Validate service', 'Service invariant', [
                new DiagnosticFieldSchema('protected_text', 'Protected text', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
                new DiagnosticFieldSchema('safe_count', 'Safe count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('safe_flag', 'Safe flag', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
            ]),
            ['safe_flag' => false, 'safe_count' => 3, 'protected_text' => str_repeat('A', 513)],
        );
        $projected = (new TrustedDeveloperDiagnosticProjector())->project(
            $this->dependencyInjectionSource($context, 'KGN-DI-9999', 'Custom title', 'Custom remediation'),
        );
        $fields = $projected->fields();

        self::assertSame('KGN-DI-9999', $projected->identifier());
        self::assertSame('Custom title', $projected->title());
        self::assertSame('Validate service', $projected->operation());
        self::assertSame('Service invariant', $projected->violatedInvariant());
        self::assertSame('Custom remediation', $projected->remediation());
        self::assertSame(['protected_text', 'safe_count', 'safe_flag'], array_map(static fn($field): string => $field->key(), $fields));
        self::assertSame([DiagnosticValueKind::Text, DiagnosticValueKind::Integer, DiagnosticValueKind::Boolean], array_map(static fn($field): DiagnosticValueKind => $field->kind(), $fields));
        self::assertSame([DiagnosticDisclosure::Protected, DiagnosticDisclosure::Safe, DiagnosticDisclosure::Safe], array_map(static fn($field): DiagnosticDisclosure => $field->disclosure(), $fields));
        self::assertSame([true, null, null], array_map(static fn($field): ?bool => $field->textWasTruncated(), $fields));
        self::assertStringEndsWith('...[truncated]', (string) $fields[0]->value());
        self::assertSame(3, $fields[1]->value());
        self::assertFalse($fields[2]->value());
    }

    public function testZeroAndMaximumFieldContextsAreAccepted(): void
    {
        $projector = new TrustedDeveloperDiagnosticProjector();
        self::assertSame([], $projector->project($this->kernelSource($this->context([]), 'KGN-KERNEL-0000'))->fields());

        $schemas = [];
        $values = [];
        for ($index = 0; $index < 16; ++$index) {
            $key = 'field_' . $index;
            $schemas[] = new DiagnosticFieldSchema($key, 'Field ' . $index, DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe);
            $values[$key] = $index;
        }
        $fields = $projector->project($this->graphSource($this->context($schemas, $values), 'KGN-GRAPH-9999'))->fields();
        self::assertCount(16, $fields);
        self::assertSame(range(0, 15), array_map(static fn($field): int => (int) $field->value(), $fields));
    }

    public function testRepeatedProjectionReturnsFreshDetachedModelsAndFields(): void
    {
        $source = $this->kernelSource($this->context([
            new DiagnosticFieldSchema('field', 'Field', DiagnosticValueKind::Text, DiagnosticDisclosure::Protected),
        ], ['field' => 'value']), 'KGN-KERNEL-1001');
        $projector = new TrustedDeveloperDiagnosticProjector();
        $first = $projector->project($source);
        $second = $projector->project($source);

        self::assertNotSame($first, $second);
        self::assertNotSame($first->fields(), $second->fields());
        self::assertNotSame($first->fields()[0], $second->fields()[0]);
        $returned = $first->fields();
        $returned = [];
        self::assertCount(1, $first->fields());
        self::assertSame([], $returned);
        foreach ([$first, ...$first->fields()] as $value) {
            $propertyTypes = array_map(static fn(\ReflectionProperty $property): string => (string) $property->getType(), (new ReflectionClass($value))->getProperties());
            self::assertNotContains(DiagnosticContext::class, $propertyTypes);
            self::assertNotContains(KernelExceptionInterface::class, $propertyTypes);
        }
    }

    public function testSourceAccessorsAreCalledExactlyOnceAndInOrder(): void
    {
        $source = new class ($this->context([])) extends RuntimeException implements KernelExceptionInterface {
            /** @var list<string> */
            public array $calls = [];
            public function __construct(private readonly DiagnosticContext $context)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'KGN-KERNEL-1001';
            }
            public function diagnosticTitle(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Title';
            }
            public function diagnosticRemediation(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Remediation';
            }
            public function diagnosticContext(): DiagnosticContext
            {
                $this->calls[] = __FUNCTION__;
                return $this->context;
            }
        };
        (new TrustedDeveloperDiagnosticProjector())->project($source);

        self::assertSame(['diagnosticId', 'diagnosticTitle', 'diagnosticRemediation', 'diagnosticContext'], $source->calls);

        $projectorSource = file_get_contents(__DIR__ . '/../../src/Developer/TrustedDeveloperDiagnosticProjector.php');
        self::assertIsString($projectorSource);
        foreach (['diagnosticId', 'diagnosticTitle', 'diagnosticRemediation', 'diagnosticContext', 'operation', 'violatedInvariant', 'fields', 'key', 'label', 'kind', 'value', 'disclosure', 'textWasTruncated'] as $accessor) {
            self::assertSame(1, substr_count($projectorSource, '->' . $accessor . '()'), $accessor);
        }
    }

    public function testDualAndTripleMarkerAmbiguityFailsBeforeAccessorUse(): void
    {
        $context = $this->context([]);
        $dual = new class ($context) extends RuntimeException implements KernelExceptionInterface, GraphExceptionInterface {
            /** @var list<string> */
            public array $calls = [];
            public function __construct(private readonly DiagnosticContext $context)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'KGN-KERNEL-1001';
            }
            public function diagnosticTitle(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Title';
            }
            public function diagnosticRemediation(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Remediation';
            }
            public function diagnosticContext(): DiagnosticContext
            {
                $this->calls[] = __FUNCTION__;
                return $this->context;
            }
        };
        $triple = new class ($context) extends RuntimeException implements KernelExceptionInterface, GraphExceptionInterface, DependencyInjectionExceptionInterface {
            /** @var list<string> */
            public array $calls = [];
            public function __construct(private readonly DiagnosticContext $context)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'KGN-KERNEL-1001';
            }
            public function diagnosticTitle(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Title';
            }
            public function diagnosticRemediation(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Remediation';
            }
            public function diagnosticContext(): DiagnosticContext
            {
                $this->calls[] = __FUNCTION__;
                return $this->context;
            }
        };

        foreach ([$dual, $triple] as $source) {
            try {
                (new TrustedDeveloperDiagnosticProjector())->project($source);
                self::fail('Ambiguous diagnostic ownership must fail.');
            } catch (Throwable $exception) {
                self::assertInstanceOf(InvalidDiagnosticProjectionException::class, $exception);
                self::assertSame(DiagnosticPresentationDiagnostic::AmbiguousDiagnosticOwner->value, $exception->diagnosticId());
                self::assertSame([], $source->calls);
            }
        }
    }

    public function testInvalidCustomMetadataMapsToExactPresentationFailures(): void
    {
        $context = $this->context([]);
        foreach ([
            [$this->kernelSource($context, 'KGN-GRAPH-1001'), DiagnosticPresentationDiagnostic::InvalidDiagnosticIdentifier],
            [$this->kernelSource($context, 'KGN-KERNEL-1001', ''), DiagnosticPresentationDiagnostic::InvalidDiagnosticTitle],
            [$this->kernelSource($context, 'KGN-KERNEL-1001', 'Title', ''), DiagnosticPresentationDiagnostic::InvalidDiagnosticRemediation],
        ] as [$source, $expected]) {
            try {
                (new TrustedDeveloperDiagnosticProjector())->project($source);
                self::fail('Invalid custom marker metadata must fail.');
            } catch (Throwable $exception) {
                self::assertInstanceOf(InvalidDiagnosticProjectionException::class, $exception);
                self::assertSame($expected->value, $exception->diagnosticId());
            }
        }

        self::assertSame('KGN-KERNEL-9999', (new TrustedDeveloperDiagnosticProjector())->project(
            $this->kernelSource($context, 'KGN-KERNEL-9999'),
        )->identifier());
    }

    public function testAccessorThrowablePropagatesUnchangedAndStopsLaterReads(): void
    {
        $failure = new RuntimeException('consumer failure');
        $source = new class ($failure, $this->context([])) extends RuntimeException implements KernelExceptionInterface {
            /** @var list<string> */
            public array $calls = [];
            public function __construct(private readonly Throwable $failure, private readonly DiagnosticContext $context)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'KGN-KERNEL-1001';
            }
            public function diagnosticTitle(): string
            {
                $this->calls[] = __FUNCTION__;
                throw $this->failure;
            }
            public function diagnosticRemediation(): string
            {
                $this->calls[] = __FUNCTION__;
                return 'Remediation';
            }
            public function diagnosticContext(): DiagnosticContext
            {
                $this->calls[] = __FUNCTION__;
                return $this->context;
            }
        };

        try {
            (new TrustedDeveloperDiagnosticProjector())->project($source);
            self::fail('A source accessor failure must propagate.');
        } catch (Throwable $exception) {
            self::assertSame($failure, $exception);
            self::assertSame(['diagnosticId', 'diagnosticTitle'], $source->calls);
        }
    }

    public function testProjectorShapeUnionAndAbsenceOfRetainedStateAreExact(): void
    {
        $reflection = new ReflectionClass(TrustedDeveloperDiagnosticProjector::class);
        $methods = array_values(array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method): bool => TrustedDeveloperDiagnosticProjector::class === $method->getDeclaringClass()->getName(),
        ));
        $parameterType = $methods[0]->getParameters()[0]->getType();
        self::assertInstanceOf(ReflectionUnionType::class, $parameterType);
        $types = [];
        foreach ($parameterType->getTypes() as $type) {
            self::assertInstanceOf(\ReflectionNamedType::class, $type);
            $types[] = $type->getName();
        }
        sort($types);
        $expected = [KernelExceptionInterface::class, GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class];
        sort($expected);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertNull($reflection->getConstructor());
        self::assertSame([], $reflection->getProperties());
        self::assertSame([], $reflection->getConstants());
        self::assertCount(1, $methods);
        self::assertSame('project', $methods[0]->getName());
        self::assertSame($expected, $types);

        $source = file_get_contents(__DIR__ . '/../../src/Developer/TrustedDeveloperDiagnosticProjector.php');
        self::assertIsString($source);
        self::assertDoesNotMatchRegularExpression(
            '/\\b(?:echo|print|printf|var_dump|fopen|file_get_contents|file_put_contents|readfile|getenv|putenv)\\b/',
            $source,
        );
        foreach (['catch', 'DiagnosticDisclosure::', 'Registry', 'Cache', 'Authorization', 'Environment'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    /**
     * @param list<DiagnosticFieldSchema> $fields
     * @param array<array-key, mixed>     $values
     */
    private function context(array $fields, array $values = []): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema('Operation', 'Invariant', $fields), $values);
    }

    private function kernelSource(
        DiagnosticContext $context,
        string $identifier,
        string $title = 'Title',
        string $remediation = 'Remediation',
    ): KernelExceptionInterface {
        return new class ($context, $identifier, $title, $remediation) extends RuntimeException implements KernelExceptionInterface {
            /** @var list<string> */
            public array $calls = [];
            public function __construct(private readonly DiagnosticContext $context, private readonly string $identifier, private readonly string $title, private readonly string $remediation)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                $this->calls[] = __FUNCTION__;
                return $this->identifier;
            }
            public function diagnosticTitle(): string
            {
                $this->calls[] = __FUNCTION__;
                return $this->title;
            }
            public function diagnosticRemediation(): string
            {
                $this->calls[] = __FUNCTION__;
                return $this->remediation;
            }
            public function diagnosticContext(): DiagnosticContext
            {
                $this->calls[] = __FUNCTION__;
                return $this->context;
            }
        };
    }

    private function graphSource(DiagnosticContext $context, string $identifier): GraphExceptionInterface
    {
        return new class ($context, $identifier) extends RuntimeException implements GraphExceptionInterface {
            public function __construct(private readonly DiagnosticContext $context, private readonly string $identifier)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                return $this->identifier;
            }
            public function diagnosticTitle(): string
            {
                return 'Title';
            }
            public function diagnosticRemediation(): string
            {
                return 'Remediation';
            }
            public function diagnosticContext(): DiagnosticContext
            {
                return $this->context;
            }
        };
    }

    private function dependencyInjectionSource(
        DiagnosticContext $context,
        string $identifier,
        string $title = 'Title',
        string $remediation = 'Remediation',
    ): DependencyInjectionExceptionInterface {
        return new class ($context, $identifier, $title, $remediation) extends RuntimeException implements DependencyInjectionExceptionInterface {
            public function __construct(private readonly DiagnosticContext $context, private readonly string $identifier, private readonly string $title, private readonly string $remediation)
            {
                parent::__construct();
            }
            public function diagnosticId(): string
            {
                return $this->identifier;
            }
            public function diagnosticTitle(): string
            {
                return $this->title;
            }
            public function diagnosticRemediation(): string
            {
                return $this->remediation;
            }
            public function diagnosticContext(): DiagnosticContext
            {
                return $this->context;
            }
        };
    }
}
