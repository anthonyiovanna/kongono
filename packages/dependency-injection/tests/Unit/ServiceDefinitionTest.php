<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Unit;

use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
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
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;

final class ServiceDefinitionTest extends TestCase
{
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
    public function testInvalidContractsAreRejected(string $contract): void
    {
        $this->assertInvalidDefinition($contract, EmptyService::class, [], 'contract');
    }

    /** @return iterable<string, array{string}> */
    public static function invalidContractProvider(): iterable
    {
        yield 'nonexistent' => ['Kongono\\DependencyInjection\\Tests\\Fixture\\MissingContract'];
        yield 'class' => [EmptyService::class];
        yield 'leading slash' => ['\\' . EmptyServiceInterface::class];
        yield 'wrong case' => [strtolower(EmptyServiceInterface::class)];
        yield 'resolver' => [ResolverInterface::class];
        yield 'resolver subinterface' => [ResolverSubinterface::class];
    }

    public function testAliasedContractIsRejected(): void
    {
        $alias = __NAMESPACE__ . '\\AliasedContract';
        self::assertTrue(class_alias(EmptyServiceInterface::class, $alias));

        $this->assertInvalidDefinition($alias, EmptyService::class, [], 'canonical');
    }

    #[DataProvider('invalidImplementationProvider')]
    public function testInvalidImplementationsAreRejected(string $implementation, string $category): void
    {
        $this->assertInvalidDefinition(EmptyServiceInterface::class, $implementation, [], $category);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidImplementationProvider(): iterable
    {
        yield 'nonexistent' => ['Kongono\\DependencyInjection\\Tests\\Fixture\\MissingImplementation', 'existing'];
        yield 'leading slash' => ['\\' . EmptyService::class, 'canonical'];
        yield 'wrong case' => [strtolower(EmptyService::class), 'canonical'];
        yield 'interface' => [EmptyServiceInterface::class, 'concrete'];
        yield 'abstract' => [AbstractService::class, 'instantiable'];
        yield 'trait' => [TraitService::class, 'existing'];
        yield 'enum' => [EnumService::class, 'instantiable'];
        yield 'private constructor' => [PrivateConstructorService::class, 'instantiable'];
        yield 'contract mismatch' => [FirstDependency::class, 'implement'];
    }

    public function testAliasedAndAnonymousImplementationsAreRejected(): void
    {
        $alias = __NAMESPACE__ . '\\AliasedImplementation';
        self::assertTrue(class_alias(EmptyService::class, $alias));
        $this->assertInvalidDefinition(EmptyServiceInterface::class, $alias, [], 'canonical');

        $anonymous = get_class(new class implements EmptyServiceInterface {});
        $this->assertInvalidDefinition(EmptyServiceInterface::class, $anonymous, [], 'instantiable');
    }

    public function testImplementationThatAlsoImplementsResolverIsRejected(): void
    {
        $this->assertInvalidDefinition(
            DualResolverServiceInterface::class,
            DualResolverService::class,
            [],
            'resolver',
        );
    }

    public function testMalformedDependencyListsAndEntriesAreRejected(): void
    {
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [1 => FirstDependencyInterface::class], 'list');
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [0 => FirstDependencyInterface::class, 2 => FirstDependencyInterface::class], 'list');
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [42], 'string');
    }

    #[DataProvider('invalidDependencyProvider')]
    public function testInvalidDependenciesAreRejected(string $dependency, string $category): void
    {
        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            CountMismatchService::class,
            [$dependency],
            $category,
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidDependencyProvider(): iterable
    {
        yield 'nonexistent' => ['Kongono\\DependencyInjection\\Tests\\Fixture\\MissingDependency', 'interface'];
        yield 'class' => [EmptyService::class, 'interface'];
        yield 'leading slash' => ['\\' . FirstDependencyInterface::class, 'canonical'];
        yield 'wrong case' => [strtolower(FirstDependencyInterface::class), 'canonical'];
        yield 'resolver' => [ResolverInterface::class, 'resolver'];
        yield 'resolver subinterface' => [ResolverSubinterface::class, 'resolver'];
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
        );
    }

    #[DataProvider('invalidConstructorProvider')]
    public function testInvalidConstructorSignaturesAreRejected(string $implementation, string $category): void
    {
        $this->assertInvalidDefinition(
            EmptyServiceInterface::class,
            $implementation,
            [FirstDependencyInterface::class],
            $category,
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidConstructorProvider(): iterable
    {
        yield 'optional and defaulted' => [OptionalParameterService::class, 'required'];
        yield 'variadic' => [VariadicParameterService::class, 'required'];
        yield 'by reference' => [ByReferenceParameterService::class, 'required'];
        yield 'untyped' => [UntypedParameterService::class, 'interface type'];
        yield 'builtin' => [BuiltinParameterService::class, 'interface type'];
        yield 'nullable' => [NullableParameterService::class, 'interface type'];
        yield 'union' => [UnionParameterService::class, 'interface type'];
        yield 'intersection' => [IntersectionParameterService::class, 'interface type'];
        yield 'self' => [SelfParameterService::class, 'does not match'];
        yield 'parent' => [ParentParameterService::class, 'does not match'];
        yield 'wrong positional interface' => [WrongParameterService::class, 'does not match'];
    }

    public function testConstructorCountMismatchIsRejected(): void
    {
        $this->assertInvalidDefinition(EmptyServiceInterface::class, CountMismatchService::class, [], 'count');
        $this->assertInvalidDefinition(EmptyServiceInterface::class, EmptyService::class, [FirstDependencyInterface::class], 'no constructor');
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
        }
    }

    /** @param array<array-key, mixed> $dependencies */
    private function assertInvalidDefinition(
        string $contract,
        string $implementation,
        array $dependencies,
        string $messageFragment,
    ): void {
        try {
            $this->newRuntimeDefinition($contract, $implementation, $dependencies);
            self::fail('Invalid definition must fail.');
        } catch (InvalidDefinitionException $exception) {
            self::assertStringContainsString(strtolower($messageFragment), strtolower($exception->getMessage()));
            self::assertInstanceOf(ExceptionInterface::class, $exception);
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
