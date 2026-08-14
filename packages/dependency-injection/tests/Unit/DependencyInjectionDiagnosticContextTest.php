<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Unit;

use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\DependencyResolver;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\DependencyInjectionException;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Tests\Fixture\AbstractService;
use Kongono\DependencyInjection\Tests\Fixture\BuiltinParameterService;
use Kongono\DependencyInjection\Tests\Fixture\ByReferenceParameterService;
use Kongono\DependencyInjection\Tests\Fixture\CountMismatchService;
use Kongono\DependencyInjection\Tests\Fixture\DualResolverService;
use Kongono\DependencyInjection\Tests\Fixture\DualResolverServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\EmptyService;
use Kongono\DependencyInjection\Tests\Fixture\EmptyServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\FirstDependency;
use Kongono\DependencyInjection\Tests\Fixture\FirstDependencyInterface;
use Kongono\DependencyInjection\Tests\Fixture\IntersectionParameterService;
use Kongono\DependencyInjection\Tests\Fixture\NullableParameterService;
use Kongono\DependencyInjection\Tests\Fixture\OptionalParameterService;
use Kongono\DependencyInjection\Tests\Fixture\OrderedService;
use Kongono\DependencyInjection\Tests\Fixture\OrderedServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\SecondDependencyInterface;
use Kongono\DependencyInjection\Tests\Fixture\SelfCycleInterface;
use Kongono\DependencyInjection\Tests\Fixture\SelfCycleService;
use Kongono\DependencyInjection\Tests\Fixture\ThrowingService;
use Kongono\DependencyInjection\Tests\Fixture\ThrowingServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\UnionParameterService;
use Kongono\DependencyInjection\Tests\Fixture\UntypedParameterService;
use Kongono\DependencyInjection\Tests\Fixture\VariadicParameterService;
use Kongono\DependencyInjection\Tests\Fixture\WrongParameterService;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Throwable;

final class DependencyInjectionDiagnosticContextTest extends TestCase
{
    public function testCatalogMetadataAndContextSchemasAreExact(): void
    {
        $expected = $this->expectedCatalog();

        self::assertSame(array_keys($expected), array_map(
            static fn(DependencyInjectionDiagnostic $diagnostic): string => $diagnostic->name,
            DependencyInjectionDiagnostic::cases(),
        ));

        foreach (DependencyInjectionDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant, $fields] = $expected[$diagnostic->name];
            $schema = $diagnostic->contextSchema();

            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $schema->operation());
            self::assertSame($invariant, $schema->violatedInvariant());
            self::assertSame($fields, array_map(
                static fn($field): array => [
                    $field->key(),
                    $field->label(),
                    $field->kind()->value,
                    $field->disclosure()->value,
                    $field->isRequired(),
                ],
                $schema->fields(),
            ));
        }
    }

    public function testCatalogDeclaresOnlyAuthorizedMethodsAndConstructsCompleteSamples(): void
    {
        $reflection = new ReflectionClass(DependencyInjectionDiagnostic::class);
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => $method->isUserDefined()
                && DependencyInjectionDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);

        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $methodNames);

        foreach (DependencyInjectionDiagnostic::cases() as $diagnostic) {
            $values = array_reverse($this->completeValues($diagnostic), true);
            $context = $diagnostic->context($values);
            $schema = $diagnostic->contextSchema();

            self::assertSame($schema->operation(), $context->operation());
            self::assertSame($schema->violatedInvariant(), $context->violatedInvariant());
            self::assertSame(
                array_map(static fn($field): string => $field->key(), $schema->fields()),
                array_map(static fn(DiagnosticField $field): string => $field->key(), $context->fields()),
            );
        }
    }

    public function testServiceDefinitionFailureContextMapsAreExact(): void
    {
        $failures = [
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition('MissingContract', EmptyService::class, []),
                DependencyInjectionDiagnostic::ContractDoesNotExist,
                ['subject_role' => 'service_contract', 'contract_name' => 'MissingContract'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition('\\' . EmptyServiceInterface::class, EmptyService::class, []),
                DependencyInjectionDiagnostic::ContractNotCanonical,
                ['subject_role' => 'service_contract', 'contract_name' => '\\' . EmptyServiceInterface::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(ResolverInterface::class, EmptyService::class, []),
                DependencyInjectionDiagnostic::ResolverContractForbidden,
                ['subject_role' => 'service_contract', 'contract_name' => ResolverInterface::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, CountMismatchService::class, ['MissingDependency']),
                DependencyInjectionDiagnostic::ContractDoesNotExist,
                ['subject_role' => 'dependency_contract', 'contract_name' => 'MissingDependency'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, CountMismatchService::class, ['\\' . FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::ContractNotCanonical,
                ['subject_role' => 'dependency_contract', 'contract_name' => '\\' . FirstDependencyInterface::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, CountMismatchService::class, [ResolverInterface::class]),
                DependencyInjectionDiagnostic::ResolverContractForbidden,
                ['subject_role' => 'dependency_contract', 'contract_name' => ResolverInterface::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, 'MissingImplementation', []),
                DependencyInjectionDiagnostic::ImplementationDoesNotExist,
                ['implementation_name' => 'MissingImplementation'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, '\\' . EmptyService::class, []),
                DependencyInjectionDiagnostic::ImplementationNotCanonical,
                ['implementation_name' => '\\' . EmptyService::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, AbstractService::class, []),
                DependencyInjectionDiagnostic::ImplementationNotInstantiable,
                ['implementation_name' => AbstractService::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, FirstDependency::class, []),
                DependencyInjectionDiagnostic::ImplementationDoesNotSatisfyContract,
                ['contract_name' => EmptyServiceInterface::class, 'implementation_name' => FirstDependency::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(DualResolverServiceInterface::class, DualResolverService::class, []),
                DependencyInjectionDiagnostic::ImplementationExposesResolver,
                ['implementation_name' => DualResolverService::class],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, EmptyService::class, [1 => FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::DependenciesNotList,
                ['entry_count' => 1, 'first_unexpected_key' => '1'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, EmptyService::class, [42]),
                DependencyInjectionDiagnostic::DependencyEntryNotString,
                ['dependency_position' => 0, 'actual_value_kind' => 'integer'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, EmptyService::class, [FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::DependenciesWithoutConstructor,
                ['implementation_name' => EmptyService::class, 'dependency_count' => 1],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, CountMismatchService::class, []),
                DependencyInjectionDiagnostic::ConstructorArityMismatch,
                ['implementation_name' => CountMismatchService::class, 'expected_parameter_count' => 0, 'actual_parameter_count' => 1],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, OptionalParameterService::class, [FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode,
                ['implementation_name' => OptionalParameterService::class, 'parameter_position' => 0, 'unsupported_mode' => 'optional+defaulted'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, UntypedParameterService::class, [FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::UnsupportedConstructorParameterType,
                ['implementation_name' => UntypedParameterService::class, 'parameter_position' => 0, 'actual_type_shape' => 'missing'],
            ],
            [
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, WrongParameterService::class, [FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch,
                [
                    'implementation_name' => WrongParameterService::class,
                    'parameter_position' => 0,
                    'expected_contract' => FirstDependencyInterface::class,
                    'actual_contract' => SecondDependencyInterface::class,
                ],
            ],
        ];

        foreach ($failures as [$operation, $diagnostic, $values]) {
            $this->assertFailureContext($operation, $diagnostic, $values);
        }

        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Definition/ServiceDefinition.php');
        self::assertStringContainsString('DependencyInjectionDiagnostic::ConstructorNotPublic', $source);
        self::assertStringContainsString("['implementation_name' => \$implementation]", $source);
    }

    public function testResolverDefinitionGraphFailureContextMapsAreExact(): void
    {
        $empty = $this->emptyDefinition();
        $ordered = new ServiceDefinition(
            OrderedServiceInterface::class,
            OrderedService::class,
            [FirstDependencyInterface::class, SecondDependencyInterface::class],
        );

        $this->assertFailureContext(
            fn(): DependencyResolver => $this->newRuntimeResolver([2 => $empty]),
            DependencyInjectionDiagnostic::DefinitionsNotList,
            ['entry_count' => 1, 'first_unexpected_key' => '2'],
        );
        $this->assertFailureContext(
            fn(): DependencyResolver => $this->newRuntimeResolver(['invalid']),
            DependencyInjectionDiagnostic::InvalidDefinitionEntry,
            ['definition_position' => 0, 'actual_value_kind' => 'string'],
        );
        $this->assertFailureContext(
            fn(): DependencyResolver => $this->newRuntimeResolver([$empty, $empty]),
            DependencyInjectionDiagnostic::DuplicateContractDefinition,
            ['contract_name' => EmptyServiceInterface::class],
        );
        $this->assertFailureContext(
            fn(): DependencyResolver => $this->newRuntimeResolver([$ordered]),
            DependencyInjectionDiagnostic::MissingDependencyDefinition,
            ['owner_contract' => OrderedServiceInterface::class, 'missing_dependency' => FirstDependencyInterface::class],
        );
        $this->assertFailureContext(
            fn(): DependencyResolver => $this->newRuntimeResolver([
                new ServiceDefinition(SelfCycleInterface::class, SelfCycleService::class, [SelfCycleInterface::class]),
            ]),
            DependencyInjectionDiagnostic::DependencyCycle,
            ['cycle_contract' => SelfCycleInterface::class],
        );
    }

    public function testRequestedServiceFailureContextMapsAreExact(): void
    {
        $resolver = new DependencyResolver([$this->emptyDefinition()]);

        $this->assertFailureContext(
            fn(): object => $this->resolveRuntime($resolver, 'MissingRequestedContract'),
            DependencyInjectionDiagnostic::RequestedContractDoesNotExist,
            ['requested_contract' => 'MissingRequestedContract'],
        );
        $this->assertFailureContext(
            fn(): object => $this->resolveRuntime($resolver, '\\' . EmptyServiceInterface::class),
            DependencyInjectionDiagnostic::RequestedContractUnsupported,
            ['requested_contract' => '\\' . EmptyServiceInterface::class, 'rejection_reason' => 'noncanonical'],
        );
        $this->assertFailureContext(
            fn(): object => $this->resolveRuntime($resolver, ResolverInterface::class),
            DependencyInjectionDiagnostic::RequestedContractUnsupported,
            ['requested_contract' => ResolverInterface::class, 'rejection_reason' => 'resolver_contract'],
        );
        $this->assertFailureContext(
            fn(): object => $this->resolveRuntime($resolver, strtolower(ResolverInterface::class)),
            DependencyInjectionDiagnostic::RequestedContractUnsupported,
            ['requested_contract' => strtolower(ResolverInterface::class), 'rejection_reason' => 'noncanonical'],
        );
        $this->assertFailureContext(
            fn(): object => $this->resolveRuntime($resolver, SecondDependencyInterface::class),
            DependencyInjectionDiagnostic::RequestedContractUndefined,
            ['requested_contract' => SecondDependencyInterface::class],
        );

        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/DependencyResolver.php');
        self::assertSame(2, substr_count($source, 'DependencyInjectionDiagnostic::ConstructedContractInvariantViolation'));
        self::assertStringContainsString("'contract_name' => \$definition->contract(),", $source);
        self::assertStringContainsString("'implementation_name' => \$definition->implementation(),", $source);
        self::assertStringContainsString("['contract_name' => \$contract, 'implementation_name' => \$implementation]", $source);
    }

    public function testClosedSafeTokenAlgorithmsAndListKeyOmissionAreExact(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);
        $closedResource = fopen('php://memory', 'r');
        self::assertIsResource($closedResource);
        fclose($closedResource);

        $kinds = [
            [null, 'null'],
            [false, 'boolean'],
            [42, 'integer'],
            [1.5, 'float'],
            [[], 'array'],
            [new stdClass(), 'object'],
            [$resource, 'resource'],
            [$closedResource, 'closed_resource'],
        ];

        foreach ($kinds as [$value, $kind]) {
            $this->assertFailureContext(
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, EmptyService::class, [$value]),
                DependencyInjectionDiagnostic::DependencyEntryNotString,
                ['dependency_position' => 0, 'actual_value_kind' => $kind],
            );
        }
        fclose($resource);

        $this->assertFailureContext(
            fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, EmptyService::class, ['unexpected' => FirstDependencyInterface::class]),
            DependencyInjectionDiagnostic::DependenciesNotList,
            ['entry_count' => 1],
        );
        $this->assertFailureContext(
            fn(): DependencyResolver => $this->newRuntimeResolver(['unexpected' => $this->emptyDefinition()]),
            DependencyInjectionDiagnostic::DefinitionsNotList,
            ['entry_count' => 1],
        );

        foreach ([
            [VariadicParameterService::class, 'optional+variadic'],
            [ByReferenceParameterService::class, 'by_reference'],
        ] as [$implementation, $mode]) {
            $this->assertFailureContext(
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, $implementation, [FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode,
                ['implementation_name' => $implementation, 'parameter_position' => 0, 'unsupported_mode' => $mode],
            );
        }

        foreach ([
            [BuiltinParameterService::class, 'builtin'],
            [NullableParameterService::class, 'nullable'],
            [UnionParameterService::class, 'union'],
            [IntersectionParameterService::class, 'intersection'],
        ] as [$implementation, $shape]) {
            $this->assertFailureContext(
                fn(): ServiceDefinition => $this->newRuntimeDefinition(EmptyServiceInterface::class, $implementation, [FirstDependencyInterface::class]),
                DependencyInjectionDiagnostic::UnsupportedConstructorParameterType,
                ['implementation_name' => $implementation, 'parameter_position' => 0, 'actual_type_shape' => $shape],
            );
        }

        $definitionSource = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Definition/ServiceDefinition.php');
        $resolverSource = (string) file_get_contents(dirname(__DIR__, 2) . '/src/DependencyResolver.php');
        foreach (['null', 'boolean', 'integer', 'float', 'string', 'array', 'object', 'resource', 'closed_resource', 'unknown'] as $kind) {
            self::assertStringContainsString("'{$kind}'", $definitionSource . $resolverSource);
        }
        self::assertStringContainsString("default => 'non_interface'", $definitionSource);
    }

    public function testManualExceptionPreservesNativeAndDiagnosticBehaviorWithSameContextIdentity(): void
    {
        $cause = new RuntimeException('cause');
        $exception = new InvalidDefinitionException(
            DependencyInjectionDiagnostic::ContractDoesNotExist,
            'message',
            ['subject_role' => 'service_contract', 'contract_name' => 'MissingContract'],
            $cause,
        );
        $context = $exception->diagnosticContext();

        self::assertSame('KGN-DI-1001', $exception->diagnosticId());
        self::assertSame(DependencyInjectionDiagnostic::ContractDoesNotExist->title(), $exception->diagnosticTitle());
        self::assertSame(DependencyInjectionDiagnostic::ContractDoesNotExist->remediation(), $exception->diagnosticRemediation());
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame($context, $exception->diagnosticContext());

        try {
            new InvalidDefinitionException(
                DependencyInjectionDiagnostic::ContractDoesNotExist,
                'invalid',
                ['subject_role' => 'service_contract'],
            );
            self::fail('An invalid manual DI context must propagate.');
        } catch (Throwable $invalidContext) {
            self::assertInstanceOf(DiagnosticsExceptionInterface::class, $invalidContext);
            self::assertNotInstanceOf(DependencyInjectionExceptionInterface::class, $invalidContext);
        }
    }

    public function testDependencyInjectionMarkerAndBaseReflectionShapesAreExactAndPackageLocal(): void
    {
        $marker = new ReflectionClass(DependencyInjectionExceptionInterface::class);
        $markerMethods = array_values(array_filter(
            $marker->getMethods(),
            static fn(ReflectionMethod $method): bool => DependencyInjectionExceptionInterface::class === $method->getDeclaringClass()->getName(),
        ));
        $markerMethodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $markerMethods);
        sort($markerMethodNames);

        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $markerMethodNames);
        self::assertSame(DiagnosticContext::class, (string) $marker->getMethod('diagnosticContext')->getReturnType());
        self::assertSame([], $marker->getMethod('diagnosticContext')->getParameters());

        foreach ([KernelExceptionInterface::class, GraphExceptionInterface::class, DiagnosticsExceptionInterface::class] as $other) {
            self::assertFalse(is_a(DependencyInjectionExceptionInterface::class, $other, true));
            self::assertFalse(is_a($other, DependencyInjectionExceptionInterface::class, true));
        }

        $base = new ReflectionClass(DependencyInjectionException::class);
        $properties = array_values(array_filter(
            $base->getProperties(),
            static fn(ReflectionProperty $property): bool => DependencyInjectionException::class === $property->getDeclaringClass()->getName(),
        ));
        self::assertSame(['diagnostic', 'diagnosticContext'], array_map(
            static fn(ReflectionProperty $property): string => $property->getName(),
            $properties,
        ));
        self::assertSame([DependencyInjectionDiagnostic::class, DiagnosticContext::class], array_map(
            static fn(ReflectionProperty $property): string => (string) $property->getType(),
            $properties,
        ));

        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }

        $methods = array_values(array_filter(
            $base->getMethods(),
            static fn(ReflectionMethod $method): bool => DependencyInjectionException::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame(['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);

        foreach ($methods as $method) {
            self::assertTrue($method->isFinal());
            self::assertTrue($method->isPublic());
        }

        $parameters = $base->getConstructor()?->getParameters();
        self::assertNotNull($parameters);
        self::assertCount(4, $parameters);
        self::assertSame([DependencyInjectionDiagnostic::class, 'string', 'array', '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $parameters,
        ));
        self::assertTrue($parameters[3]->isDefaultValueAvailable());
        self::assertNull($parameters[3]->getDefaultValue());

        $markerSource = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Exception/ExceptionInterface.php');
        $baseSource = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Exception/DependencyInjectionException.php');
        self::assertStringContainsString('Kongono\\Diagnostics\\Context\\DiagnosticContext', $markerSource);
        self::assertStringNotContainsString('DependencyInjectionDiagnostic', $markerSource);
        self::assertStringNotContainsString('Kongono\\Diagnostics\\Diagnostic', $markerSource);
        self::assertStringNotContainsString('Kongono\\Diagnostics\\Exception', $baseSource);

        foreach ([
            dirname(__DIR__, 2) . '/src/Diagnostic/DependencyInjectionDiagnostic.php',
            dirname(__DIR__, 2) . '/src/Exception/DependencyInjectionException.php',
            dirname(__DIR__, 2) . '/src/Definition/ServiceDefinition.php',
            dirname(__DIR__, 2) . '/src/DependencyResolver.php',
        ] as $sourcePath) {
            $source = (string) file_get_contents($sourcePath);
            foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'getenv(', '$_ENV', '$_SERVER', 'error_log(', '->render(', '->log(', '->record(', '->cache('] as $forbiddenCall) {
                self::assertStringNotContainsString($forbiddenCall, $source);
            }
        }
    }

    public function testContextValuesAndReturnedArraysAreDetachedAndExceptionsDoNotShareContext(): void
    {
        $contractName = 'MissingContract';
        $values = ['subject_role' => 'service_contract', 'contract_name' => &$contractName];
        $first = new InvalidDefinitionException(DependencyInjectionDiagnostic::ContractDoesNotExist, 'first', $values);
        $second = new InvalidDefinitionException(DependencyInjectionDiagnostic::ContractDoesNotExist, 'second', $values);
        $fields = $first->diagnosticContext()->fields();
        $firstSchema = DependencyInjectionDiagnostic::ContractDoesNotExist->contextSchema();
        $secondSchema = DependencyInjectionDiagnostic::ContractDoesNotExist->contextSchema();

        $contractName = 'changed';
        array_pop($fields);

        self::assertNotSame($first->diagnosticContext(), $second->diagnosticContext());
        self::assertNotSame($firstSchema, $secondSchema);
        self::assertSame('service_contract', $first->diagnosticContext()->fields()[0]->value());
        self::assertSame('MissingContract', $first->diagnosticContext()->fields()[1]->value());
        self::assertCount(2, $first->diagnosticContext()->fields());
        self::assertCount(2, $second->diagnosticContext()->fields());
    }

    public function testNativeFailuresRemainNativeAndPublicMarkerDoesNotExposeInternalCatalog(): void
    {
        $name = __NAMESPACE__ . '\\RequestedAutoloadFailure';
        $autoloadFailure = new RuntimeException('requested autoload failure');
        $loader = static function (string $class) use ($name, $autoloadFailure): void {
            if ($name === $class) {
                throw $autoloadFailure;
            }
        };
        spl_autoload_register($loader);

        try {
            try {
                $this->resolveRuntime(new DependencyResolver([]), $name);
                self::fail('Autoloader failure must propagate.');
            } catch (Throwable $caught) {
                self::assertNotInstanceOf(DependencyInjectionExceptionInterface::class, $caught);
                self::assertSame($autoloadFailure, $caught);
            }
        } finally {
            spl_autoload_unregister($loader);
        }

        ThrowingService::$attempts = 0;
        $resolver = new DependencyResolver([
            new ServiceDefinition(ThrowingServiceInterface::class, ThrowingService::class, []),
        ]);

        try {
            $resolver->resolve(ThrowingServiceInterface::class);
            self::fail('Consumer constructor failure must propagate.');
        } catch (RuntimeException $caught) {
            self::assertSame(RuntimeException::class, $caught::class);
            self::assertSame('consumer constructor failure', $caught->getMessage());
            self::assertFalse(method_exists($caught, 'diagnosticContext'));
        }

        $marker = new ReflectionClass(DependencyInjectionExceptionInterface::class);
        foreach ($marker->getMethods() as $method) {
            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType) {
                self::assertNotSame(DependencyInjectionDiagnostic::class, $returnType->getName());
            }

            foreach ($method->getParameters() as $parameter) {
                self::assertNotSame(DependencyInjectionDiagnostic::class, (string) $parameter->getType());
            }
        }
    }

    /**
     * @param callable(): mixed                   $operation
     * @param array<string, string|int|bool>     $expectedValues
     */
    private function assertFailureContext(
        callable $operation,
        DependencyInjectionDiagnostic $diagnostic,
        array $expectedValues,
    ): void {
        try {
            $operation();
            self::fail('The deliberate Dependency Injection operation must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(DependencyInjectionExceptionInterface::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($this->representedValues($expectedValues), $this->contextValues($exception->diagnosticContext()));
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
        }
    }

    /** @return array<string, string|int|bool> */
    private function contextValues(DiagnosticContext $context): array
    {
        $values = [];

        foreach ($context->fields() as $field) {
            $values[$field->key()] = $field->value();
        }

        return $values;
    }

    /**
     * @param array<string, string|int|bool> $values
     *
     * @return array<string, string|int|bool>
     */
    private function representedValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $values[$key] = str_replace('\\', '\\\\', $value);
            }
        }

        return $values;
    }

    /** @param array<array-key, mixed> $dependencies */
    private function newRuntimeDefinition(string $contract, string $implementation, array $dependencies): ServiceDefinition
    {
        $definition = (new ReflectionClass(ServiceDefinition::class))->newInstanceArgs([
            $contract,
            $implementation,
            $dependencies,
        ]);
        self::assertInstanceOf(ServiceDefinition::class, $definition);

        return $definition;
    }

    /** @param array<array-key, mixed> $definitions */
    private function newRuntimeResolver(array $definitions): DependencyResolver
    {
        $resolver = (new ReflectionClass(DependencyResolver::class))->newInstanceArgs([$definitions]);
        self::assertInstanceOf(DependencyResolver::class, $resolver);

        return $resolver;
    }

    private function resolveRuntime(DependencyResolver $resolver, string $contract): object
    {
        $resolved = (new ReflectionMethod($resolver, 'resolve'))->invoke($resolver, $contract);
        self::assertIsObject($resolved);

        return $resolved;
    }

    private function emptyDefinition(): ServiceDefinition
    {
        return new ServiceDefinition(EmptyServiceInterface::class, EmptyService::class, []);
    }

    /** @return array<string, string|int|bool> */
    private function completeValues(DependencyInjectionDiagnostic $diagnostic): array
    {
        return match ($diagnostic) {
            DependencyInjectionDiagnostic::ContractDoesNotExist,
            DependencyInjectionDiagnostic::ContractNotCanonical,
            DependencyInjectionDiagnostic::ResolverContractForbidden => ['subject_role' => 'service_contract', 'contract_name' => 'Example\\Contract'],
            DependencyInjectionDiagnostic::ImplementationDoesNotExist,
            DependencyInjectionDiagnostic::ImplementationNotCanonical,
            DependencyInjectionDiagnostic::ImplementationNotInstantiable,
            DependencyInjectionDiagnostic::ImplementationExposesResolver,
            DependencyInjectionDiagnostic::ConstructorNotPublic => ['implementation_name' => 'Example\\Implementation'],
            DependencyInjectionDiagnostic::ImplementationDoesNotSatisfyContract => ['contract_name' => 'Example\\Contract', 'implementation_name' => 'Example\\Implementation'],
            DependencyInjectionDiagnostic::DependenciesNotList,
            DependencyInjectionDiagnostic::DefinitionsNotList => ['entry_count' => 2, 'first_unexpected_key' => '4'],
            DependencyInjectionDiagnostic::DependencyEntryNotString => ['dependency_position' => 1, 'actual_value_kind' => 'object'],
            DependencyInjectionDiagnostic::DependenciesWithoutConstructor => ['implementation_name' => 'Example\\Implementation', 'dependency_count' => 1],
            DependencyInjectionDiagnostic::ConstructorArityMismatch => ['implementation_name' => 'Example\\Implementation', 'expected_parameter_count' => 2, 'actual_parameter_count' => 1],
            DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode => ['implementation_name' => 'Example\\Implementation', 'parameter_position' => 0, 'unsupported_mode' => 'optional+defaulted'],
            DependencyInjectionDiagnostic::UnsupportedConstructorParameterType => ['implementation_name' => 'Example\\Implementation', 'parameter_position' => 0, 'actual_type_shape' => 'union'],
            DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch => [
                'implementation_name' => 'Example\\Implementation',
                'parameter_position' => 0,
                'expected_contract' => 'Example\\Expected',
                'actual_contract' => 'Example\\Actual',
            ],
            DependencyInjectionDiagnostic::InvalidDefinitionEntry => ['definition_position' => 1, 'actual_value_kind' => 'string'],
            DependencyInjectionDiagnostic::DuplicateContractDefinition => ['contract_name' => 'Example\\Contract'],
            DependencyInjectionDiagnostic::MissingDependencyDefinition => ['owner_contract' => 'Example\\Owner', 'missing_dependency' => 'Example\\Missing'],
            DependencyInjectionDiagnostic::DependencyCycle => ['cycle_contract' => 'Example\\Cycle'],
            DependencyInjectionDiagnostic::RequestedContractDoesNotExist,
            DependencyInjectionDiagnostic::RequestedContractUndefined => ['requested_contract' => 'Example\\Requested'],
            DependencyInjectionDiagnostic::RequestedContractUnsupported => ['requested_contract' => 'Example\\Requested', 'rejection_reason' => 'noncanonical'],
            DependencyInjectionDiagnostic::ConstructedContractInvariantViolation => ['contract_name' => 'Example\\Contract', 'implementation_name' => 'Example\\Implementation'],
        };
    }

    /** @return array<string, array{string, string, string, string, string, list<array{string, string, string, string, bool}>}> */
    private function expectedCatalog(): array
    {
        return [
            'ContractDoesNotExist' => ['KGN-DI-1001', 'Service or dependency contract does not name an existing interface', 'Supply the exact declared interface name and ensure it can autoload', 'Validate service contract', 'Contract must name an existing interface', [['subject_role', 'Subject role', 'text', 'safe', true], ['contract_name', 'Contract name', 'text', 'protected', true]]],
            'ContractNotCanonical' => ['KGN-DI-1002', 'Service or dependency contract is not canonical', "Use the declaration's exact case and spelling without a leading slash or alias", 'Validate service contract', 'Contract must use its canonical declared name', [['subject_role', 'Subject role', 'text', 'safe', true], ['contract_name', 'Contract name', 'text', 'protected', true]]],
            'ResolverContractForbidden' => ['KGN-DI-1003', 'Resolver contract or subinterface is used as a service/dependency contract', 'Remove resolver injection and keep resolution at the composition root', 'Validate service contract', 'Resolver contract cannot enter the service graph', [['subject_role', 'Subject role', 'text', 'safe', true], ['contract_name', 'Contract name', 'text', 'protected', true]]],
            'ImplementationDoesNotExist' => ['KGN-DI-1004', 'Implementation does not name an existing class', 'Supply the exact autoloadable implementation class', 'Validate implementation', 'Implementation must name an existing class', [['implementation_name', 'Implementation name', 'text', 'protected', true]]],
            'ImplementationNotCanonical' => ['KGN-DI-1005', 'Implementation class name is not canonical', "Use the declaration's exact class name without alias or case variation", 'Validate implementation', 'Implementation must use its canonical declared name', [['implementation_name', 'Implementation name', 'text', 'protected', true]]],
            'ImplementationNotInstantiable' => ['KGN-DI-1006', 'Implementation is not publicly instantiable', 'Use a named, concrete, publicly instantiable class', 'Validate implementation', 'Implementation must be publicly instantiable', [['implementation_name', 'Implementation name', 'text', 'protected', true]]],
            'ImplementationDoesNotSatisfyContract' => ['KGN-DI-1007', 'Implementation does not implement the declared service contract', 'Select an implementation satisfying that exact interface', 'Validate implementation', 'Implementation must satisfy the declared contract', [['contract_name', 'Contract name', 'text', 'protected', true], ['implementation_name', 'Implementation name', 'text', 'protected', true]]],
            'ImplementationExposesResolver' => ['KGN-DI-1008', 'Implementation exposes the Kongono resolver contract', 'Remove resolver implementation/injection from the service graph', 'Validate implementation', 'Implementation must not expose the resolver contract', [['implementation_name', 'Implementation name', 'text', 'protected', true]]],
            'DependenciesNotList' => ['KGN-DI-1009', 'Dependencies input is not a zero-based consecutive list', 'Reindex dependencies as a PHP list', 'Validate dependency list', 'Dependencies must be a PHP list', [['entry_count', 'Entry count', 'integer', 'safe', true], ['first_unexpected_key', 'First unexpected key', 'text', 'protected', false]]],
            'DependencyEntryNotString' => ['KGN-DI-1010', 'Dependency-list entry is not a canonical-name string', 'Supply an interface-name string at the reported position', 'Validate dependency entry', 'Dependency entry must be a contract-name string', [['dependency_position', 'Dependency position', 'integer', 'safe', true], ['actual_value_kind', 'Actual value kind', 'text', 'safe', true]]],
            'DependenciesWithoutConstructor' => ['KGN-DI-1011', 'Implementation has no constructor but declares dependencies', 'Remove the dependency entries or define a matching public constructor', 'Validate constructor dependencies', 'Dependency declarations require a constructor', [['implementation_name', 'Implementation name', 'text', 'protected', true], ['dependency_count', 'Dependency count', 'integer', 'safe', true]]],
            'ConstructorNotPublic' => ['KGN-DI-1012', 'Implementation constructor is not public', 'Make the constructor public or choose another implementation', 'Validate constructor', 'Constructor must be public', [['implementation_name', 'Implementation name', 'text', 'protected', true]]],
            'ConstructorArityMismatch' => ['KGN-DI-1013', 'Constructor parameter count differs from dependency count', 'Make the ordered definition and constructor arity identical', 'Validate constructor arity', 'Constructor and definition arity must match exactly', [['implementation_name', 'Implementation name', 'text', 'protected', true], ['expected_parameter_count', 'Expected parameter count', 'integer', 'safe', true], ['actual_parameter_count', 'Actual parameter count', 'integer', 'safe', true]]],
            'UnsupportedConstructorParameterMode' => ['KGN-DI-1014', 'Constructor parameter is optional, defaulted, variadic, or by-reference', 'Use a required, non-defaulted, non-variadic, by-value parameter', 'Validate constructor parameter mode', 'Parameter must be required, by-value, non-variadic, and non-defaulted', [['implementation_name', 'Implementation name', 'text', 'protected', true], ['parameter_position', 'Parameter position', 'integer', 'safe', true], ['unsupported_mode', 'Unsupported mode', 'text', 'safe', true]]],
            'UnsupportedConstructorParameterType' => ['KGN-DI-1015', 'Constructor parameter lacks one non-nullable interface type', 'Use one non-nullable named interface type', 'Validate constructor parameter type', 'Parameter must have one non-nullable interface type', [['implementation_name', 'Implementation name', 'text', 'protected', true], ['parameter_position', 'Parameter position', 'integer', 'safe', true], ['actual_type_shape', 'Actual type shape', 'text', 'safe', true]]],
            'ConstructorDependencyTypeMismatch' => ['KGN-DI-1016', 'Constructor parameter type differs from the dependency at that position', 'Align the exact interface type and ordered dependency', 'Validate constructor dependency type', 'Parameter type must equal the ordered dependency contract', [['implementation_name', 'Implementation name', 'text', 'protected', true], ['parameter_position', 'Parameter position', 'integer', 'safe', true], ['expected_contract', 'Expected contract', 'text', 'protected', true], ['actual_contract', 'Actual contract', 'text', 'protected', true]]],
            'DefinitionsNotList' => ['KGN-DI-1017', 'Definitions input is not a zero-based consecutive list', 'Reindex definitions as a PHP list', 'Validate definition list', 'Definitions must be a PHP list', [['entry_count', 'Entry count', 'integer', 'safe', true], ['first_unexpected_key', 'First unexpected key', 'text', 'protected', false]]],
            'InvalidDefinitionEntry' => ['KGN-DI-1018', 'Definition-list entry is not a `ServiceDefinition`', 'Replace the reported entry with a validated definition', 'Validate definition entry', 'Every definitions-list entry must be a ServiceDefinition', [['definition_position', 'Definition position', 'integer', 'safe', true], ['actual_value_kind', 'Actual value kind', 'text', 'safe', true]]],
            'DuplicateContractDefinition' => ['KGN-DI-1019', 'More than one definition uses the same contract', 'Retain exactly one definition for that contract', 'Validate definition uniqueness', 'Each service contract may have one definition', [['contract_name', 'Contract name', 'text', 'protected', true]]],
            'MissingDependencyDefinition' => ['KGN-DI-1020', 'A declared dependency has no definition', 'Add the missing contract definition or remove the dependency', 'Validate definition graph completeness', 'Every dependency contract must have a definition', [['owner_contract', 'Owner contract', 'text', 'protected', true], ['missing_dependency', 'Missing dependency', 'text', 'protected', true]]],
            'DependencyCycle' => ['KGN-DI-1021', 'Definition graph contains a dependency cycle', 'Break the reported cycle through explicit design changes', 'Validate definition graph acyclicity', 'Definition graph must be acyclic', [['cycle_contract', 'Cycle contract', 'text', 'protected', true]]],
            'RequestedContractDoesNotExist' => ['KGN-DI-1022', 'Requested service name does not resolve to an existing interface', 'Request the exact autoloadable interface name', 'Resolve service', 'Requested service must name an existing interface', [['requested_contract', 'Requested contract', 'text', 'protected', true]]],
            'RequestedContractUnsupported' => ['KGN-DI-1023', 'Requested interface is noncanonical or is the resolver contract', 'Use the exact supported service interface and never resolve the resolver itself', 'Resolve service', 'Requested contract must be canonical and not the resolver contract', [['requested_contract', 'Requested contract', 'text', 'protected', true], ['rejection_reason', 'Rejection reason', 'text', 'safe', true]]],
            'RequestedContractUndefined' => ['KGN-DI-1024', 'No definition exists for the requested service contract', 'Register an explicit definition at the composition root', 'Resolve service', 'Requested service must have a definition', [['requested_contract', 'Requested contract', 'text', 'protected', true]]],
            'ConstructedContractInvariantViolation' => ['KGN-DI-1025', 'Constructed object violates a previously validated service contract', 'Treat this as a framework invariant failure and inspect the definition/implementation boundary', 'Construct service', 'Constructed object must satisfy the validated contract', [['contract_name', 'Contract name', 'text', 'protected', true], ['implementation_name', 'Implementation name', 'text', 'protected', true]]],
        ];
    }
}
