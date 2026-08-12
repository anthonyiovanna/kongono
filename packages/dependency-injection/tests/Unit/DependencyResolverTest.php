<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Tests\Unit;

use Kongono\DependencyInjection\Contract\ResolverInterface;
use Kongono\DependencyInjection\Definition\ServiceDefinition;
use Kongono\DependencyInjection\DependencyResolver;
use Kongono\DependencyInjection\Exception\ExceptionInterface;
use Kongono\DependencyInjection\Exception\InvalidDefinitionException;
use Kongono\DependencyInjection\Exception\UnknownServiceException;
use Kongono\DependencyInjection\Tests\Fixture\CycleAInterface;
use Kongono\DependencyInjection\Tests\Fixture\CycleAService;
use Kongono\DependencyInjection\Tests\Fixture\CycleBInterface;
use Kongono\DependencyInjection\Tests\Fixture\CycleBService;
use Kongono\DependencyInjection\Tests\Fixture\DiamondService;
use Kongono\DependencyInjection\Tests\Fixture\DiamondServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\DuplicateDependencyService;
use Kongono\DependencyInjection\Tests\Fixture\DuplicateDependencyServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\EmptyService;
use Kongono\DependencyInjection\Tests\Fixture\EmptyServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\FirstDependency;
use Kongono\DependencyInjection\Tests\Fixture\FirstDependencyInterface;
use Kongono\DependencyInjection\Tests\Fixture\LeftService;
use Kongono\DependencyInjection\Tests\Fixture\LeftServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\LongCycleAInterface;
use Kongono\DependencyInjection\Tests\Fixture\LongCycleAService;
use Kongono\DependencyInjection\Tests\Fixture\LongCycleBInterface;
use Kongono\DependencyInjection\Tests\Fixture\LongCycleBService;
use Kongono\DependencyInjection\Tests\Fixture\LongCycleCInterface;
use Kongono\DependencyInjection\Tests\Fixture\LongCycleCService;
use Kongono\DependencyInjection\Tests\Fixture\OrderedService;
use Kongono\DependencyInjection\Tests\Fixture\OrderedServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\OtherSelfCycleInterface;
use Kongono\DependencyInjection\Tests\Fixture\OtherSelfCycleService;
use Kongono\DependencyInjection\Tests\Fixture\RightService;
use Kongono\DependencyInjection\Tests\Fixture\RightServiceInterface;
use Kongono\DependencyInjection\Tests\Fixture\SecondDependency;
use Kongono\DependencyInjection\Tests\Fixture\SecondDependencyInterface;
use Kongono\DependencyInjection\Tests\Fixture\SelfCycleInterface;
use Kongono\DependencyInjection\Tests\Fixture\SelfCycleService;
use Kongono\DependencyInjection\Tests\Fixture\ThrowingService;
use Kongono\DependencyInjection\Tests\Fixture\ThrowingServiceInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;

final class DependencyResolverTest extends TestCase
{
    public function testEmptyDefinitionListIsValid(): void
    {
        $resolver = new DependencyResolver([]);

        self::assertInstanceOf(ResolverInterface::class, $resolver);
    }

    public function testMalformedDefinitionListsAndEntriesAreRejected(): void
    {
        $definition = $this->emptyDefinition();

        $this->assertInvalidResolver([1 => $definition], 'list');
        $this->assertInvalidResolver([0 => $definition, 2 => $definition], 'list');
        $this->assertInvalidResolver(['not a definition'], 'ServiceDefinition');
    }

    public function testDuplicateContractsAreRejected(): void
    {
        $this->assertInvalidResolver([$this->emptyDefinition(), $this->emptyDefinition()], EmptyServiceInterface::class);
    }

    public function testMissingTransitiveDependenciesAreRejected(): void
    {
        $definition = new ServiceDefinition(
            OrderedServiceInterface::class,
            OrderedService::class,
            [FirstDependencyInterface::class, SecondDependencyInterface::class],
        );

        $this->assertInvalidResolver([$definition], FirstDependencyInterface::class);
    }

    public function testMissingDependencyFailureIsCanonicalAcrossInputOrder(): void
    {
        $ordered = new ServiceDefinition(
            OrderedServiceInterface::class,
            OrderedService::class,
            [FirstDependencyInterface::class, SecondDependencyInterface::class],
        );
        $diamond = new ServiceDefinition(
            DiamondServiceInterface::class,
            DiamondService::class,
            [LeftServiceInterface::class, RightServiceInterface::class],
        );

        foreach ([[$ordered, $diamond], [$diamond, $ordered]] as $definitions) {
            try {
                new DependencyResolver($definitions);
                self::fail('Missing definitions must fail.');
            } catch (InvalidDefinitionException $exception) {
                self::assertStringContainsString(DiamondServiceInterface::class, $exception->getMessage());
                self::assertStringContainsString(LeftServiceInterface::class, $exception->getMessage());
            }
        }
    }

    public function testDirectTwoNodeAndLongerCyclesAreRejected(): void
    {
        $this->assertInvalidResolver([
            new ServiceDefinition(SelfCycleInterface::class, SelfCycleService::class, [SelfCycleInterface::class]),
        ], 'cycle');

        $this->assertInvalidResolver([
            new ServiceDefinition(CycleAInterface::class, CycleAService::class, [CycleBInterface::class]),
            new ServiceDefinition(CycleBInterface::class, CycleBService::class, [CycleAInterface::class]),
        ], 'cycle');

        $this->assertInvalidResolver([
            new ServiceDefinition(LongCycleAInterface::class, LongCycleAService::class, [LongCycleBInterface::class]),
            new ServiceDefinition(LongCycleBInterface::class, LongCycleBService::class, [LongCycleCInterface::class]),
            new ServiceDefinition(LongCycleCInterface::class, LongCycleCService::class, [LongCycleAInterface::class]),
        ], 'cycle');
    }

    public function testCycleFailureIsCanonicalAcrossInputOrder(): void
    {
        $first = new ServiceDefinition(SelfCycleInterface::class, SelfCycleService::class, [SelfCycleInterface::class]);
        $other = new ServiceDefinition(
            OtherSelfCycleInterface::class,
            OtherSelfCycleService::class,
            [OtherSelfCycleInterface::class],
        );

        foreach ([[$first, $other], [$other, $first]] as $definitions) {
            try {
                new DependencyResolver($definitions);
                self::fail('Cycles must fail.');
            } catch (InvalidDefinitionException $exception) {
                self::assertStringContainsString('cycle', strtolower($exception->getMessage()));
                self::assertStringContainsString(OtherSelfCycleInterface::class, $exception->getMessage());
            }
        }
    }

    public function testEquivalentValidSetsResolveRegardlessOfInputOrder(): void
    {
        $definitions = $this->orderedDefinitions();
        $first = new DependencyResolver($definitions);
        $second = new DependencyResolver(array_reverse($definitions));

        self::assertInstanceOf(OrderedService::class, $first->resolve(OrderedServiceInterface::class));
        self::assertInstanceOf(OrderedService::class, $second->resolve(OrderedServiceInterface::class));
    }

    public function testDependencyFreeAndNestedServicesResolveInDeclaredOrder(): void
    {
        $resolver = new DependencyResolver($this->orderedDefinitions());
        $service = $resolver->resolve(OrderedServiceInterface::class);

        self::assertInstanceOf(OrderedService::class, $service);
        self::assertInstanceOf(FirstDependency::class, $service->first);
        self::assertInstanceOf(SecondDependency::class, $service->second);
        self::assertInstanceOf(FirstDependency::class, $resolver->resolve(FirstDependencyInterface::class));
    }

    public function testEachTopLevelResolutionIsTransient(): void
    {
        $resolver = new DependencyResolver([$this->emptyDefinition()]);

        self::assertNotSame(
            $resolver->resolve(EmptyServiceInterface::class),
            $resolver->resolve(EmptyServiceInterface::class),
        );
    }

    public function testDuplicateDependencyEdgesReceiveDistinctInstances(): void
    {
        $resolver = new DependencyResolver([
            $this->firstDependencyDefinition(),
            new ServiceDefinition(
                DuplicateDependencyServiceInterface::class,
                DuplicateDependencyService::class,
                [FirstDependencyInterface::class, FirstDependencyInterface::class],
            ),
        ]);
        $service = $resolver->resolve(DuplicateDependencyServiceInterface::class);

        self::assertInstanceOf(DuplicateDependencyService::class, $service);
        self::assertNotSame($service->first, $service->second);
    }

    public function testDiamondGraphDoesNotShareLowerDependency(): void
    {
        $resolver = new DependencyResolver([
            $this->firstDependencyDefinition(),
            new ServiceDefinition(LeftServiceInterface::class, LeftService::class, [FirstDependencyInterface::class]),
            new ServiceDefinition(RightServiceInterface::class, RightService::class, [FirstDependencyInterface::class]),
            new ServiceDefinition(
                DiamondServiceInterface::class,
                DiamondService::class,
                [LeftServiceInterface::class, RightServiceInterface::class],
            ),
        ]);
        $service = $resolver->resolve(DiamondServiceInterface::class);

        self::assertInstanceOf(DiamondService::class, $service);
        self::assertInstanceOf(LeftService::class, $service->left);
        self::assertInstanceOf(RightService::class, $service->right);
        self::assertNotSame($service->left->dependency, $service->right->dependency);
    }

    public function testInvalidNoncanonicalAndUndefinedRequestsAreUnknownServices(): void
    {
        $resolver = new DependencyResolver([$this->emptyDefinition()]);
        $alias = __NAMESPACE__ . '\\AliasedRequestedContract';
        self::assertTrue(class_alias(EmptyServiceInterface::class, $alias));

        foreach ([
            'MissingRequestedContract',
            EmptyService::class,
            '\\' . EmptyServiceInterface::class,
            strtolower(EmptyServiceInterface::class),
            $alias,
            SecondDependencyInterface::class,
        ] as $contract) {
            try {
                $this->resolveRuntime($resolver, $contract);
                self::fail('Unknown request must fail.');
            } catch (UnknownServiceException $exception) {
                self::assertInstanceOf(ExceptionInterface::class, $exception);
                self::assertStringContainsString($contract, $exception->getMessage());
            }
        }
    }

    public function testConstructorFailurePropagatesAndEachRetryConstructsAgain(): void
    {
        ThrowingService::$attempts = 0;
        $resolver = new DependencyResolver([
            new ServiceDefinition(ThrowingServiceInterface::class, ThrowingService::class, []),
        ]);

        for ($attempt = 1; $attempt <= 2; ++$attempt) {
            try {
                $resolver->resolve(ThrowingServiceInterface::class);
                self::fail('Consumer constructor failure must propagate.');
            } catch (RuntimeException $exception) {
                self::assertNotInstanceOf(ExceptionInterface::class, $exception);
                self::assertSame('consumer constructor failure', $exception->getMessage());
                self::assertSame($attempt, ThrowingService::$attempts);
            }
        }
    }

    public function testResolverAndInterfaceShapeAreExact(): void
    {
        $resolver = new ReflectionClass(DependencyResolver::class);
        $contract = new ReflectionClass(ResolverInterface::class);

        self::assertTrue($resolver->isFinal());
        self::assertTrue($resolver->isReadOnly());
        self::assertTrue($resolver->implementsInterface(ResolverInterface::class));
        self::assertSame(['__construct', 'resolve'], $this->publicMethodNames($resolver));
        self::assertTrue($contract->isInterface());
        self::assertSame([], $contract->getInterfaceNames());
        self::assertSame(['resolve'], $this->publicMethodNames($contract));
    }

    public function testAutoloaderFailureDuringRequestedContractCheckPropagatesUnchanged(): void
    {
        $name = __NAMESPACE__ . '\\RequestedAutoloadFailure';
        $failure = new RuntimeException('requested autoload failure');
        $loader = static function (string $class) use ($name, $failure): void {
            if ($name === $class) {
                throw $failure;
            }
        };
        spl_autoload_register($loader);

        try {
            try {
                $this->resolveRuntime(new DependencyResolver([]), $name);
                self::fail('Autoloader failure must propagate.');
            } catch (Throwable $caught) {
                self::assertSame($failure, $caught);
            }
        } finally {
            spl_autoload_unregister($loader);
        }
    }

    /** @return list<ServiceDefinition> */
    private function orderedDefinitions(): array
    {
        return [
            $this->firstDependencyDefinition(),
            new ServiceDefinition(SecondDependencyInterface::class, SecondDependency::class, []),
            new ServiceDefinition(
                OrderedServiceInterface::class,
                OrderedService::class,
                [FirstDependencyInterface::class, SecondDependencyInterface::class],
            ),
        ];
    }

    private function emptyDefinition(): ServiceDefinition
    {
        return new ServiceDefinition(EmptyServiceInterface::class, EmptyService::class, []);
    }

    private function firstDependencyDefinition(): ServiceDefinition
    {
        return new ServiceDefinition(FirstDependencyInterface::class, FirstDependency::class, []);
    }

    /** @param array<array-key, mixed> $definitions */
    private function assertInvalidResolver(array $definitions, string $messageFragment): void
    {
        try {
            (new ReflectionClass(DependencyResolver::class))->newInstanceArgs([$definitions]);
            self::fail('Invalid definition set must fail.');
        } catch (InvalidDefinitionException $exception) {
            self::assertStringContainsString(strtolower($messageFragment), strtolower($exception->getMessage()));
            self::assertInstanceOf(ExceptionInterface::class, $exception);
        }
    }

    private function resolveRuntime(DependencyResolver $resolver, string $contract): object
    {
        $resolved = (new ReflectionMethod($resolver, 'resolve'))->invoke($resolver, $contract);

        if (!is_object($resolved)) {
            throw new RuntimeException('Resolver returned a non-object through its public runtime boundary.');
        }

        return $resolved;
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
