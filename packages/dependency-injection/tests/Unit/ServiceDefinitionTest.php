<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Unit;

use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic;
use Kongono\DependencyInjection\Exception\ExceptionInterface;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Tests\Fixture\AbstractService;
use Kongono\DependencyInjection\Tests\Fixture\BuiltinParameterService;
use Kongono\DependencyInjection\Tests\Fixture\ByReferenceParameterService;
use Kongono\DependencyInjection\Tests\Fixture\CountMismatchService;
use Kongono\DependencyInjection\Tests\Fixture\DualResolverService;
use Kongono\DependencyInjection\Tests\Fixture\DualResolverServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\EmptyService;
use Kongono\DependencyInjection\Tests\Fixture\EmptyServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\EnumService;
use Kongono\DependencyInjection\Tests\Fixture\FirstDependency;
use Kongono\DependencyInjection\Tests\Fixture\FirstDependencyInterface;
use Kongono\DependencyInjection\Tests\Fixture\InheritedConstructorService;
use Kongono\DependencyInjection\Tests\Fixture\IntersectionParameterService;
use Kongono\DependencyInjection\Tests\Fixture\NullableParameterService;
use Kongono\DependencyInjection\Tests\Fixture\OptionalParameterService;
use Kongono\DependencyInjection\Tests\Fixture\OrderedService;
use Kongono\DependencyInjection\Tests\Fixture\OrderedServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\ParentParameterService;
use Kongono\DependencyInjection\Tests\Fixture\PrivateConstructorService;
use Kongono\DependencyInjection\Tests\Fixture\ResolverSubinterface;
use Kongono\DependencyInjection\Tests\Fixture\SecondDependencyInterface;
use Kongono\DependencyInjection\Tests\Fixture\SelfParameterService;
use Kongono\DependencyInjection\Tests\Fixture\TraitService;
use Kongono\DependencyInjection\Tests\Fixture\UnionParameterService;
use Kongono\DependencyInjection\Tests\Fixture\UntypedParameterService;
use Kongono\DependencyInjection\Tests\Fixture\VariadicParameterService;
use Kongono\DependencyInjection\Tests\Fixture\WrongParameterService;
use Kongono\DependencyInjection\Tests\Fixture\ZeroParameterService;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;

final class ServiceDefinitionTest extends TestCase
{
    public function testDependencyInjectionDiagnosticCatalogAndMetadataContract(): void
    {
        $expected = [
            ['ContractDoesNotExist', 'KGN-DI-1001', 'Service or dependency contract does not name an existing interface', 'Supply the exact declared interface name and ensure it can autoload'],
            ['ContractNotCanonical', 'KGN-DI-1002', 'Service or dependency contract is not canonical', "Use the declaration's exact case and spelling without a leading slash or alias"],
            ['ResolverContractForbidden', 'KGN-DI-1003', 'Resolver contract or subinterface is used as a service/dependency contract', 'Remove resolver injection and keep resolution at the composition root'],
            ['ImplementationDoesNotExist', 'KGN-DI-1004', 'Implementation does not name an existing class', 'Supply the exact autoloadable implementation class'],
            ['ImplementationNotCanonical', 'KGN-DI-1005', 'Implementation class name is not canonical', "Use the declaration's exact class name without alias or case variation"],
            ['ImplementationNotInstantiable', 'KGN-DI-1006', 'Implementation is not publicly instantiable', 'Use a named, concrete, publicly instantiable class'],
            ['ImplementationDoesNotSatisfyContract', 'KGN-DI-1007', 'Implementation does not implement the declared service contract', 'Select an implementation satisfying that exact interface'],
            ['ImplementationExposesResolver', 'KGN-DI-1008', 'Implementation exposes the Kongono resolver contract', 'Remove resolver implementation/injection from the service graph'],
            ['DependenciesNotList', 'KGN-DI-1009', 'Dependencies input is not a zero-based consecutive list', 'Reindex dependencies as a PHP list'],
            ['DependencyEntryNotString', 'KGN-DI-1010', 'Dependency-list entry is not a canonical-name string', 'Supply an interface-name string at the reported position'],
            ['DependenciesWithoutConstructor', 'KGN-DI-1011', 'Implementation has no constructor but declares dependencies', 'Remove the dependency entries or define a matching public constructor'],
            ['ConstructorNotPublic', 'KGN-DI-1012', 'Implementation constructor is not public', 'Make the constructor public or choose another implementation'],
            ['ConstructorArityMismatch', 'KGN-DI-1013', 'Constructor parameter count differs from dependency count', 'Make the ordered definition and constructor arity identical'],
            ['UnsupportedConstructorParameterMode', 'KGN-DI-1014', 'Constructor parameter is optional, defaulted, variadic, or by-reference', 'Use a required, non-defaulted, non-variadic, by-value parameter'],
            ['UnsupportedConstructorParameterType', 'KGN-DI-1015', 'Constructor parameter lacks one non-nullable interface type', 'Use one non-nullable named interface type'],
            ['ConstructorDependencyTypeMismatch', 'KGN-DI-1016', 'Constructor parameter type differs from the dependency at that position', 'Align the exact interface type and ordered dependency'],
            ['DefinitionsNotList', 'KGN-DI-1017', 'Definitions input is not a zero-based consecutive list', 'Reindex definitions as a PHP list'],
            ['InvalidDefinitionEntry', 'KGN-DI-1018', 'Definition-list entry is not a `ServiceDefinition`', 'Replace the reported entry with a validated definition'],
            ['DuplicateContractDefinition', 'KGN-DI-1019', 'More than one definition uses the same contract', 'Retain exactly one definition for that contract'],
            ['MissingDependencyDefinition', 'KGN-DI-1020', 'A declared dependency has no definition', 'Add the missing contract definition or remove the dependency'],
            ['DependencyCycle', 'KGN-DI-1021', 'Definition graph contains a dependency cycle', 'Break the reported cycle through explicit design changes'],
            ['RequestedContractDoesNotExist', 'KGN-DI-1022', 'Requested service name does not resolve to an existing interface', 'Request the exact autoloadable interface name'],
            ['RequestedContractUnsupported', 'KGN-DI-1023', 'Requested interface is noncanonical or is the resolver contract', 'Use the exact supported service interface and never resolve the resolver itself'],
            ['RequestedContractUndefined', 'KGN-DI-1024', 'No definition exists for the requested service contract', 'Register an explicit definition at the composition root'],
            ['ConstructedContractInvariantViolation', 'KGN-DI-1025', 'Constructed object violates a previously validated service contract', 'Treat this as a framework invariant failure and inspect the definition/implementation boundary'],
        ];
        $actual = array_map(
            static fn(DependencyInjectionDiagnostic $diagnostic): array => [
                $diagnostic->name,
                $diagnostic->value,
                $diagnostic->title(),
                $diagnostic->remediation(),
            ],
            DependencyInjectionDiagnostic::cases(),
        );
        $reflection = new ReflectionClass(DependencyInjectionDiagnostic::class);
        $declaredMethods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(\ReflectionMethod $method): bool => $method->isUserDefined()
                && DependencyInjectionDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), $declaredMethods);
        sort($methodNames);
        $declaredProperties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => DependencyInjectionDiagnostic::class === $property->getDeclaringClass()->getName(),
        ));

        self::assertSame($expected, $actual);
        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $methodNames);
        self::assertSame(
            ['name', 'value'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $declaredProperties),
        );
        self::assertFalse($reflection->hasMethod('__toString'));

        $cause = new RuntimeException('cause');
        $exception = new InvalidDefinitionException(
            DependencyInjectionDiagnostic::ContractDoesNotExist,
            'message',
            ['subject_role' => 'service_contract', 'contract_name' => 'MissingContract'],
            $cause,
        );

        self::assertSame('KGN-DI-1001', $exception->diagnosticId());
        self::assertSame(DependencyInjectionDiagnostic::ContractDoesNotExist->title(), $exception->diagnosticTitle());
        self::assertSame(DependencyInjectionDiagnostic::ContractDoesNotExist->remediation(), $exception->diagnosticRemediation());
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
        self::assertInstanceOf(DiagnosticContext::class, $exception->diagnosticContext());
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
    }

    public function testCanonicalDefinitionPreservesValuesAndDependencyOrder(): void
    {
        $definition = new ServiceDefinition(
            OrderedServiceInterface::class,
            OrderedService::class,
            [FirstDependencyInterface::class, SecondDependencyInterface::class],
        );

        self::assertSame(OrderedServiceInterface::class, $definition->contract());
        self::assertSame(OrderedService::class, $definition->implementation());
        self::assertSame(
            [FirstDependencyInterface::class, SecondDependencyInterface::class],
            $definition->dependencies(),
        );
    }

    public function testDependencyInputAndOutputMutationCannotChangeDefinition(): void
    {
        $referenced = FirstDependencyInterface::class;
        $dependencies = [&$referenced];
        $definition = new ServiceDefinition(
            EmptyServiceInterface::class,
            InheritedConstructorService::class,
            $dependencies,
        );
        $dependencies[0] = SecondDependencyInterface::class;
        $referenced = SecondDependencyInterface::class;
        $returned = $definition->dependencies();
        $returned[0] = SecondDependencyInterface::class;

        self::assertSame([FirstDependencyInterface::class], $definition->dependencies());
    }

    public function testModelShapeIsExactFinalAndReadonly(): void
    {
        $reflection = new ReflectionClass(ServiceDefinition::class);
        $properties = $reflection->getProperties();
        $propertyNames = array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $properties);
        sort($propertyNames);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(['contract', 'dependencies', 'implementation'], $propertyNames);

        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertFalse($property->isStatic());
        }

        self::assertSame(
            ['__construct', 'contract', 'dependencies', 'implementation'],
            $this->publicMethodNames($reflection),
        );
    }

    #[DataProvider('invalidContractProvider')]
    public function testInvalidContractsAreRejected(
        string $contract,
        DependencyInjectionDiagnostic $diagnostic,
    ): void {
        $this->assertInvalidDefinition($contract, EmptyService::class, [], 'contract', $diagnostic);
    }

    /** @return iterable<string, array{string, DependencyInjectionDiagnostic}> */
    public static function invalidContractProvider(): iterable
    {
        yield 'nonexistent' => ['Kongono\\DependencyInjection\\Tests\\Fixture\\MissingContract', DependencyInjectionDiagnostic::ContractDoesNotExist];
        yield 'class' => [EmptyService::class, DependencyInjectionDiagnostic::ContractDoesNotExist];
        yield 'leading slash' => ['\\' . EmptyServiceInterface::class, DependencyInjectionDiagnostic::ContractNotCanonical];
        yield 'wrong case' => [strtolower(EmptyServiceInterface::class), DependencyInjectionDiagnostic::ContractNotCanonical];
        yield 'resolver' => [ResolverInterface::class, DependencyInjectionDiagnostic::ResolverContractForbidden];
        yield 'resolver subinterface' => [ResolverSubinterface::class, DependencyInjectionDiagnostic::ResolverContractForbidden];
    }

    public function testAliasedContractIsRejected(): void
    {
        $alias = __NAMESPACE__ . '\\AliasedContract';
        self::assertTrue(class_alias(EmptyServiceInterface::class, $alias));

        $this->assertInvalidDefinition($alias, EmptyService::class, [], 'canonical', DependencyInjectionDiagnostic::ContractNotCanonical);
    }

    #[DataProvider('invalidImplementationProvider')]
    public function testInvalidImplementationsAreRejected(
        string $implementation,
        string $category,
        DependencyInjectionDiagnostic $diagnostic,
    ): void {
        $this->assertInvalidDefinition(EmptyServiceInterface::class, $implementation, [], $category, $diagnostic);
    }

    /** @return iterable<string, array{string, string, DependencyInjectionDiagnostic}> */
    public static function invalidImplementationProvider(): iterable
    {
        yield 'nonexistent' => ['Kongono\\DependencyInjection\\Tests\\Fixture\\MissingImplementation', 'existing', DependencyInjectionDiagnostic::ImplementationDoesNotExist];
        yield 'leading slash' => ['\\' . EmptyService::class, 'canonical', DependencyInjectionDiagnostic::ImplementationNotCanonical];
        yield 'wrong case' => [strtolower(EmptyService::class), 'canonical', DependencyInjectionDiagnostic::ImplementationNotCanonical];
        yield 'interface' => [EmptyServiceInterface::class, 'concrete', DependencyInjectionDiagnostic::ImplementationDoesNotExist];
        yield 'abstract' => [AbstractService::class, 'instantiable', DependencyInjectionDiagnostic::ImplementationNotInstantiable];
        yield 'trait' => [TraitService::class, 'existing', DependencyInjectionDiagnostic::ImplementationDoesNotExist];
        yield 'enum' => [EnumService::class, 'instantiable', DependencyInjectionDiagnostic::ImplementationNotInstantiable];
        yield 'private constructor' => [PrivateConstructorService::class, 'instantiable', DependencyInjectionDiagnostic::ImplementationNotInstantiable];
        yield 'contract mismatch' => [FirstDependency::class, 'implement', DependencyInjectionDiagnostic::ImplementationDoesNotSatisfyContract];
    }

    public function testAliasedAndAnonymousImplementationsAreRejected(): void
    {
        $alias = __NAMESPACE__ . '\\AliasedImplementation';
        self::assertTrue(class_alias(EmptyService::class, $alias));
        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            $alias,
            [],
            'canonical',
            DependencyInjectionDiagnostic::ImplementationNotCanonical,
        );

        $anonymous = get_class(new class implements EmptyServiceInterface {});
        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            $anonymous,
            [],
            'instantiable',
            DependencyInjectionDiagnostic::ImplementationNotInstantiable,
        );
    }

    public function testImplementationThatAlsoImplementsResolverIsRejected(): void
    {
        $this->assertInvalidDefinition(
            DualResolverServiceInterface::class,
            DualResolverService::class,
            [],
            'resolver',
            DependencyInjectionDiagnostic::ImplementationExposesResolver,
        );
    }

    public function testMalformedDependencyListsAndEntriesAreRejected(): void
    {
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [1 => FirstDependencyInterface::class], 'list', DependencyInjectionDiagnostic::DependenciesNotList);
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [0 => FirstDependencyInterface::class, 2 => FirstDependencyInterface::class], 'list', DependencyInjectionDiagnostic::DependenciesNotList);
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [42], 'string', DependencyInjectionDiagnostic::DependencyEntryNotString);
    }

    #[DataProvider('invalidDependencyProvider')]
    public function testInvalidDependenciesAreRejected(
        string $dependency,
        string $category,
        DependencyInjectionDiagnostic $diagnostic,
    ): void {
        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            CountMismatchService::class,
            [$dependency],
            $category,
            $diagnostic,
        );
    }

    /** @return iterable<string, array{string, string, DependencyInjectionDiagnostic}> */
    public static function invalidDependencyProvider(): iterable
    {
        yield 'nonexistent' => ['Kongono\\DependencyInjection\\Tests\\Fixture\\MissingDependency', 'interface', DependencyInjectionDiagnostic::ContractDoesNotExist];
        yield 'class' => [EmptyService::class, 'interface', DependencyInjectionDiagnostic::ContractDoesNotExist];
        yield 'leading slash' => ['\\' . FirstDependencyInterface::class, 'canonical', DependencyInjectionDiagnostic::ContractNotCanonical];
        yield 'wrong case' => [strtolower(FirstDependencyInterface::class), 'canonical', DependencyInjectionDiagnostic::ContractNotCanonical];
        yield 'resolver' => [ResolverInterface::class, 'resolver', DependencyInjectionDiagnostic::ResolverContractForbidden];
        yield 'resolver subinterface' => [ResolverSubinterface::class, 'resolver', DependencyInjectionDiagnostic::ResolverContractForbidden];
    }

    public function testAliasedDependencyIsRejected(): void
    {
        $alias = __NAMESPACE__ . '\\AliasedDependency';
        self::assertTrue(class_alias(FirstDependencyInterface::class, $alias));

        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            CountMismatchService::class,
            [$alias],
            'canonical',
            DependencyInjectionDiagnostic::ContractNotCanonical,
        );
    }

    #[DataProvider('invalidConstructorProvider')]
    public function testInvalidConstructorSignaturesAreRejected(
        string $implementation,
        string $category,
        DependencyInjectionDiagnostic $diagnostic,
    ): void {
        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            $implementation,
            [FirstDependencyInterface::class],
            $category,
            $diagnostic,
        );
    }

    /** @return iterable<string, array{string, string, DependencyInjectionDiagnostic}> */
    public static function invalidConstructorProvider(): iterable
    {
        yield 'optional and defaulted' => [OptionalParameterService::class, 'required', DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode];
        yield 'variadic' => [VariadicParameterService::class, 'required', DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode];
        yield 'by reference' => [ByReferenceParameterService::class, 'required', DependencyInjectionDiagnostic::UnsupportedConstructorParameterMode];
        yield 'untyped' => [UntypedParameterService::class, 'interface type', DependencyInjectionDiagnostic::UnsupportedConstructorParameterType];
        yield 'builtin' => [BuiltinParameterService::class, 'interface type', DependencyInjectionDiagnostic::UnsupportedConstructorParameterType];
        yield 'nullable' => [NullableParameterService::class, 'interface type', DependencyInjectionDiagnostic::UnsupportedConstructorParameterType];
        yield 'union' => [UnionParameterService::class, 'interface type', DependencyInjectionDiagnostic::UnsupportedConstructorParameterType];
        yield 'intersection' => [IntersectionParameterService::class, 'interface type', DependencyInjectionDiagnostic::UnsupportedConstructorParameterType];
        yield 'self' => [SelfParameterService::class, 'does not match', DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch];
        yield 'parent' => [ParentParameterService::class, 'does not match', DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch];
        yield 'wrong positional interface' => [WrongParameterService::class, 'does not match', DependencyInjectionDiagnostic::ConstructorDependencyTypeMismatch];
    }

    public function testConstructorCountMismatchIsRejected(): void
    {
        $this->assertInvalidDefinition(EmptyServiceInterface::class, CountMismatchService::class, [], 'count', DependencyInjectionDiagnostic::ConstructorArityMismatch);
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [FirstDependencyInterface::class], 'no constructor', DependencyInjectionDiagnostic::DependenciesWithoutConstructor);
    }

    public function testNoConstructorZeroParameterAndInheritedConstructorAreValid(): void
    {
        self::assertSame(EmptyService::class, (new ServiceDefinition(EmptyServiceInterface::class, EmptyService::class, []))->implementation());
        self::assertSame(ZeroParameterService::class, (new ServiceDefinition(EmptyServiceInterface::class, ZeroParameterService::class, []))->implementation());
        self::assertSame(
            [FirstDependencyInterface::class],
            (new ServiceDefinition(
                EmptyServiceInterface::class,
                InheritedConstructorService::class,
                [FirstDependencyInterface::class],
            ))->dependencies(),
        );
    }

    public function testAutoloaderFailurePropagatesUnchanged(): void
    {
        $name = __NAMESPACE__ . '\\AutoloadFailureContract';
        $failure = new RuntimeException('autoload failure');
        $loader = static function (string $class) use ($name, $failure): void {
            if ($name === $class) {
                throw $failure;
            }
        };
        spl_autoload_register($loader);

        try {
            try {
                $this->newRuntimeDefinition($name, EmptyService::class, []);
                self::fail('Autoloader failure must propagate.');
            } catch (Throwable $caught) {
                self::assertSame($failure, $caught);
            }
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    public function testDeliberateFailuresUseOnlyDependencyInjectionMarker(): void
    {
        try {
            $this->newRuntimeDefinition('MissingContract', EmptyService::class, []);
            self::fail('Invalid definition must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(InvalidDefinitionException::class, $exception);
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertFalse((new ReflectionClass(KernelExceptionInterface::class))->isInstance($exception));
            self::assertSame(DependencyInjectionDiagnostic::ContractDoesNotExist->value, $exception->diagnosticId());
        }
    }

    /** @param array<array-key, mixed> $dependencies */
    private function assertInvalidDefinition(
        string $contract,
        string $implementation,
        array $dependencies,
        string $messageFragment,
        DependencyInjectionDiagnostic $diagnostic,
    ): void {
        try {
            $this->newRuntimeDefinition($contract, $implementation, $dependencies);
            self::fail('Invalid definition must fail.');
        } catch (InvalidDefinitionException $exception) {
            self::assertStringContainsString(strtolower($messageFragment), strtolower($exception->getMessage()));
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertInstanceOf(DiagnosticContext::class, $exception->diagnosticContext());
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
        }
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

    /**
     * @template T of object
     *
     * @param ReflectionClass<T> $reflection
     *
     * @return list<string>
     */
    private function publicMethodNames(ReflectionClass $reflection): array
    {
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        );
        sort($methods);

        return $methods;
    }
}
